<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate;

use Illuminate\Support\Facades\Event;
use SergKulich\LaravelKeyRotate\Events\KeyRotatedEvent;
use SergKulich\LaravelKeyRotate\Listeners\KeyRotatedListener;

final class KeyRotateService
{
    private ?KeyRotatedListener $listener = null;

    private bool $subscribed = false;

    public function getListener(): KeyRotatedListener
    {
        if (is_null($this->listener)) {
            $this->listener = new KeyRotatedListener;
        }

        return $this->listener;
    }

    public function withListener(): KeyRotatedListener
    {
        if (! $this->subscribed) {
            Event::listen(KeyRotatedEvent::class, $this->getListener());
            $this->subscribed = true;
        }

        return $this->getListener();
    }

    /**
     * @return class-string<KeyRotatedEvent>
     */
    public static function getEventClass(): string
    {
        return KeyRotatedEvent::class;
    }

    /**
     * @return class-string<KeyRotatedListener>
     */
    public static function getListenerClass(): string
    {
        return KeyRotatedListener::class;
    }
}
