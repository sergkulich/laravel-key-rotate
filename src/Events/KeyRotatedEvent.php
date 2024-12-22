<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class KeyRotatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        //
    }
}
