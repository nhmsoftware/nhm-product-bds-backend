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
        $ffprobe = trim((string) shell_exec('which ffprobe 2>/dev/null'));

        if (blank($ffprobe)) {
            Log::error('ExtractVideoDuration: ffprobe not found. Install with: sudo apt install -y ffmpeg');
            return null;
        }

        $process = new Process([
            $ffprobe,
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $filePath,
        ]);

        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::warning('ExtractVideoDuration: ffprobe failed', [
                'lesson_id' => $this->lessonId,
                'error'     => $process->getErrorOutput(),
            ]);
            return null;
        }

        $raw = trim($process->getOutput());

        if (!is_numeric($raw)) {
            return null;
        }

        return (int) round((float) $raw);
    }
}
