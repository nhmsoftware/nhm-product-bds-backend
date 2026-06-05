<?php

declare(strict_types=1);

namespace App\Modules\Area\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Modules\Area\Events\AreaCommentAdded;
use Illuminate\Support\Facades\Log;

class NotifyUsersOnAreaCommentAdded implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AreaCommentAdded $event): void
    {
        $comment = $event->comment;

        // Broadcast to socket.io or FCM could go here
        Log::info("New comment added on area {$comment->area_id} by user {$comment->user_id}");
    }
}
