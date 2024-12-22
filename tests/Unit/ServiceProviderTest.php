<?php

use SergKulich\LaravelKeyRotate\Events\KeyRotatedEvent;
use SergKulich\LaravelKeyRotate\Facades\KeyRotate;
use SergKulich\LaravelKeyRotate\Listeners\KeyRotatedListener;

it('returns the copy of the listener', function () {
    expect(KeyRotate::getListener())
        ->toBeInstanceOf(KeyRotatedListener::class);
});

it('manipulates with single copy of the listener', function () {
    expect(KeyRotate::withListener())
        ->toBe(KeyRotate::withListener())
        ->toBe(KeyRotate::getListener());
});

it('returns event class', function () {
    expect(KeyRotate::getEventClass())
        ->toBe(KeyRotatedEvent::class);
});

it('returns listener class', function () {
    expect(KeyRotate::getListenerClass())
        ->toBe(KeyRotatedListener::class);
});
