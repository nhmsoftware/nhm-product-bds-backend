<?php

declare(strict_types=1);

namespace App\Modules\Area\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Area\Models\LotComment;

final class LotCommentAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LotComment $comment
    ) {
    }
}
