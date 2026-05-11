<?php

namespace App\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $traceId;
    public float $createdAt;
    public ?int $actorId; // Người thực hiện hành động này

    public function __construct(?int $actorId = null, ?string $traceId = null)
    {
        // Nếu không có traceId (từ request), tự tạo mới để truy vết
        $this->traceId = $traceId ?? (string) Str::uuid();
        $this->createdAt = microtime(true);
        $this->actorId = $actorId ?? (auth()->id() ?? null);
    }
}
