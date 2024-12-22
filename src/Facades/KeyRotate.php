<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate\Facades;

use Illuminate\Support\Facades\Facade;
use SergKulich\LaravelKeyRotate\KeyRotateService;
use SergKulich\LaravelKeyRotate\Listeners\KeyRotatedListener;

/**
 * @method static KeyRotatedListener getListener()
 * @method static KeyRotatedListener withListener()
 */
final class KeyRotate extends Facade
{
    /**
     * @return class-string<KeyRotateService>
     */
    protected static function getFacadeAccessor(): string
    {
        return KeyRotateService::class;
    }
}
