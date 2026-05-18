<?php

namespace App\Modules\LegalVideo\Events;

use App\Modules\LegalVideo\Models\LegalVideo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LegalVideoViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LegalVideo $video,
        public readonly ?string $userId = null
    ) {
    }
}
