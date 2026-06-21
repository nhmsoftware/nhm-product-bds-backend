<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Learning\Models\CourseLesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ExtractVideoDurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        private readonly string $lessonId,
    ) {}

    public function handle(): void
    {
        $lesson = CourseLesson::find($this->lessonId);

        if (!$lesson || blank($lesson->video_url)) {
            return;
        }

        $filePath = $this->resolveFilePath($lesson->video_url);

        if (!$filePath || !file_exists($filePath)) {
            Log::warning('ExtractVideoDuration: file not found', [
                'lesson_id' => $this->lessonId,
                'video_url' => $lesson->video_url,
                'resolved'  => $filePath,
            ]);
            return;
        }

        $duration = $this->extractDuration($filePath);

        if ($duration === null) {
            return;
        }

        $lesson->updateQuietly(['duration_seconds' => $duration]);
    }

    private function resolveFilePath(string $videoUrl): ?string
    {
        // /storage/path/to/video.mp4  →  strip /storage/  →  Storage::disk('public')->path(...)
        $path = (string) preg_replace('#^/?storage/#', '', $videoUrl);

        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    private function extractDuration(string $filePath): ?int
    {
        Log::info('ExtractVideoDuration: start', [
            'lesson_id' => $this->lessonId,
            'file'      => $filePath,
            'exists'    => file_exists($filePath),
            'size'      => file_exists($filePath) ? filesize($filePath) : null,
        ]);

        // Try ffprobe first
        $ffprobe = trim((string) shell_exec('which ffprobe 2>/dev/null'));

        if (!blank($ffprobe)) {
            $process = new Process([
                $ffprobe, '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $filePath,
            ]);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $raw = trim($process->getOutput());
                if (is_numeric($raw)) {
                    $duration = (int) round((float) $raw);
                    Log::info('ExtractVideoDuration: ffprobe ok', ['lesson_id' => $this->lessonId, 'duration' => $duration]);
                    return $duration;
                }
            }

            Log::warning('ExtractVideoDuration: ffprobe failed', [
                'lesson_id' => $this->lessonId,
                'stderr'    => $process->getErrorOutput(),
            ]);
        } else {
            Log::warning('ExtractVideoDuration: ffprobe not found, trying PHP MP4 parser', ['lesson_id' => $this->lessonId]);
        }

        // Fallback: pure PHP — parse MP4/MOV moov→mvhd atom
        $duration = $this->parseMP4Duration($filePath);

        if ($duration !== null) {
            Log::info('ExtractVideoDuration: PHP parser ok', ['lesson_id' => $this->lessonId, 'duration' => $duration]);
        } else {
            Log::error('ExtractVideoDuration: all methods failed — install ffmpeg: sudo apt install -y ffmpeg', ['lesson_id' => $this->lessonId]);
        }

        return $duration;
    }

    private function parseMP4Duration(string $filePath): ?int
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return null;
        }

        $fileSize = filesize($filePath);
        $offset   = 0;

        while ($offset < $fileSize - 8) {
            fseek($handle, $offset);
            $header = fread($handle, 8);
            if (strlen($header) < 8) {
                break;
            }

            $boxSize = unpack('N', substr($header, 0, 4))[1];
            $boxType = substr($header, 4, 4);

            if ($boxSize < 8) {
                break;
            }

            if ($boxType === 'moov') {
                $moovEnd = $offset + $boxSize;
                $inner   = $offset + 8;

                while ($inner < $moovEnd - 8) {
                    fseek($handle, $inner);
                    $sub = fread($handle, 8);
                    if (strlen($sub) < 8) {
                        break;
                    }

                    $subSize = unpack('N', substr($sub, 0, 4))[1];
                    $subType = substr($sub, 4, 4);

                    if ($subType === 'mvhd') {
                        $mvhd = fread($handle, 20);
                        if (strlen($mvhd) < 20) {
                            break;
                        }
                        $version = ord($mvhd[0]);
                        if ($version === 1) {
                            $extra     = fread($handle, 8);
                            $timescale = unpack('N', substr($mvhd, 16, 4))[1];
                            $duration  = (unpack('N', substr($extra, 0, 4))[1] * 4294967296)
                                       + unpack('N', substr($extra, 4, 4))[1];
                        } else {
                            $timescale = unpack('N', substr($mvhd, 8, 4))[1];
                            $duration  = unpack('N', substr($mvhd, 12, 4))[1];
                        }

                        fclose($handle);
                        return $timescale > 0 ? (int) round($duration / $timescale) : null;
                    }

                    if ($subSize < 8) {
                        break;
                    }
                    $inner += $subSize;
                }
            }

            $offset += $boxSize;
        }

        fclose($handle);
        return null;
    }
}
