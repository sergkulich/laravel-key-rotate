<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\File;
use SergKulich\LaravelKeyRotate\Events\KeyRotatedEvent;

final class KeyRotateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'key:rotate
                            {--force : Force the operation to run when in production}';

    /**
     * @var string
     */
    protected $description = 'Rotate the application key';

    public function handle(): int
    {
        // First, we will try to add current APP_KEY to APP_PREVIOUS_KEYS and update .env file.
        /** @var list<non-empty-string> $keys */
        $keys = array_filter(array_unique([
            config('app.key'),
            ...config('app.previous_keys', []), // @phpstan-ignore-line
        ]));

        if (! $this->setPreviousKeysInEnvironmentFile($keys)) {
            return Command::FAILURE;
        }

        config(['app.previous_keys' => $keys]);

        $this->components->info('Application previous keys updated successfully.');

        // Next, we will generate new APP_KEY with laravel built in command.
        $this->call('key:generate', ['--force' => true]);

        // Next, we will dispatch event so custom listeners could react to it.
        KeyRotatedEvent::dispatch();

        return Command::SUCCESS;
    }

    /**
     * @param  list<non-empty-string>  $keys
     */
    protected function setPreviousKeysInEnvironmentFile(array $keys): bool
    {
        if ($keys === []) {
            return false;
        }

        if (! $this->confirmToProceed(callback: fn () => app()->environment(['testing', 'production']))) {
            return false;
        }

        return $this->writeNewEnvironmentFileWith($keys);
    }

    /**
     * @param  list<string>  $previousKeys
     */
    protected function writeNewEnvironmentFileWith(array $previousKeys): bool
    {
        $input = File::get(app()->environmentFilePath());

        $pattern = $this->previousKeysReplacementPattern();
        $replacement = 'APP_PREVIOUS_KEYS='.implode(',', $previousKeys);

        if (! preg_match($this->previousKeysReplacementPattern(), $input)) {
            $pattern = $this->keyReplacementPattern();
            $replacement = 'APP_KEY='.config('app.key').PHP_EOL.$replacement;
        }

        $replaced = preg_replace($pattern, $replacement, $input);
        if ($replaced === $input || $replaced === null) {
            $this->error('Unable to set application previous keys.');

            return false;
        }

        File::put(app()->environmentFilePath(), $replaced);

        return true;
    }

    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('='.config('app.key'), '/');

        return "/^APP_KEY{$escaped}/m";
    }

    protected function previousKeysReplacementPattern(): string
    {
        // @phpstan-ignore-next-line
        $escaped = preg_quote('='.implode(',', config('app.previous_keys')), '/');

        return "/^APP_PREVIOUS_KEYS{$escaped}/m";
    }
}
