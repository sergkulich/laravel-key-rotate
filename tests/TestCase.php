<?php

namespace SergKulich\LaravelKeyRotate\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SergKulich\LaravelKeyRotate\KeyRotateServiceProvider;

abstract class TestCase extends BaseTestCase
{
    private ?string $tmp = __DIR__.DIRECTORY_SEPARATOR.'tmp';

    private ?string $env = null;

    private ?string $database = null;

    protected function getEnvironmentSetUp($app): void
    {
        $hex = bin2hex(random_bytes(16));

        // ENV
        $this->env = '.env.'.$hex;
        copy(base_path('.env'), $this->tmp.DIRECTORY_SEPARATOR.$this->env);

        $app->useEnvironmentPath($this->tmp);
        $app->loadEnvironmentFrom($this->env);

        // DB
        $this->database = $this->tmp.'/database.'.$hex.'.sqlite';
        copy(base_path('database/database.sqlite'), $this->database);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', $this->database);
    }

    protected function setUp(): void
    {
        parent::setUp();

        //
    }

    protected function tearDown(): void
    {
        unlink($this->tmp.DIRECTORY_SEPARATOR.$this->env);
        unlink($this->database);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            KeyRotateServiceProvider::class,
        ];
    }
}
