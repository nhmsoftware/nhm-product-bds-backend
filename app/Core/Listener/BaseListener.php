<?php

namespace App\Core\Listener;

use App\Core\Logs\Logging;
use Throwable;

abstract class BaseListener
{
    public function failed(mixed $event, Throwable $exception): void
    {
        // Centralized error handling cho tất cả Listener
        Logging::error(
            sprintf(
                "[%s] Listener Failed: %s. Trace ID: %s",
                static::class,
                $exception->getMessage(),
                $event->traceId ?? 'N/A'
            ),
            $exception
        );
    }
}
