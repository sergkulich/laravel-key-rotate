<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SergKulich\LaravelKeyRotate\Console\KeyRotateCommand;

final class KeyRotateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                KeyRotateCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->singleton(KeyRotateService::class, function (Application $app) {
            return new KeyRotateService;
        });
    }
}
