<?php

declare(strict_types=1);

namespace SergKulich\LaravelKeyRotate\Listeners;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use UnexpectedValueException;

final class KeyRotatedListener
{
    /**
     * @var list<non-empty-string|class-string<Castable>>
     */
    private array $casts = [
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:object',
        AsEncryptedArrayObject::class,
        AsEncryptedCollection::class,
    ];

    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $dirs = [];

    /**
     * @var list<class-string<Model>>
     */
    private array $models = [];

    /**
     * @var array<class-string<Model>, list<non-empty-string>>
     */
    private array $withoutModelFields = [];

    public function __construct()
    {
        $this->withDir(app()->path(), app()->getNamespace()); // @phpstan-ignore-line
    }

    public function __invoke(): void
    {
        $this->handle();
    }

    public function handle(): void
    {
        foreach ($this->dirs as $path => $namespace) {
            $this->discoverModels($path, $namespace);
        }

        foreach ($this->models as $model) {
            if (! $fields = $this->getModelFields($model)) {
                continue;
            }

            $model::query()->withoutGlobalScopes()->withoutEagerLoads()->cursor()
                ->each(function (Model $item) use ($fields) {
                    foreach ($fields as $field) {
                        $item->{$field} = (fn () => $item->{$field})();
                    }
                    $item->save();
                });
        }
    }

    /**
     * @return list<non-empty-string|class-string<Castable>>
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * @param  non-empty-string|class-string<Castable>  $cast
     *
     * @throw UnexpectedValueException
     */
    public function withCast(string $cast): self
    {
        $allowedImplementations = [
            Castable::class,
            CastsAttributes::class,
            CastsInboundAttributes::class,
        ];

        if (class_exists($cast) && ! array_intersect($allowedImplementations, class_implements($cast) ?: [])) {
            throw new UnexpectedValueException(
                'Cast class must implement "Castable" or "CastsAttributes" or "CastsInboundAttributes".'
            );
        }

        if (! class_exists($cast) && ! str_starts_with($cast, 'encrypted')) {
            throw new UnexpectedValueException('Cast must be one of Laravel predefined "encrypted" ones.');
        }

        $this->casts = array_unique([...$this->casts, $cast]);

        return $this;
    }

    /**
     * @param  non-empty-string|class-string<Castable>  $cast
     */
    public function withoutCast(string $cast): self
    {
        $this->casts = array_diff($this->casts, [$cast]);

        return $this;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    public function getDirs(): array
    {
        return $this->dirs;
    }

    /**
     * @param  non-empty-string  $path
     * @param  non-empty-string  $namespace
     */
    public function withDir(string $path, string $namespace): self
    {
        if (! File::isDirectory($path)) {
            throw new UnexpectedValueException('Directory "'.$path.'" does not exist.');
        }

        $this->dirs[$path] = $namespace;

        return $this;
    }

    /**
     * @param  non-empty-string  $path
     */
    public function withoutDir(string $path): self
    {
        unset($this->dirs[$path]);

        return $this;
    }

    private function discoverModels(string $path, string $namespace): void
    {
        foreach (File::allFiles($path) as $file) {
            $class = sprintf('%s%s\\%s',
                $namespace,
                strtr($file->getRelativePath(), DIRECTORY_SEPARATOR, '\\'),
                $file->getFilenameWithoutExtension()
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var class-string<Model> $class */
            $this->models[] = $class;
        }

        $this->models = array_unique($this->models);
    }

    /**
     * @return list<class-string<Model>>
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function withModel(string $model): self
    {
        if (! class_exists($model)) {
            throw new UnexpectedValueException('String must be a class name.');
        }

        $reflection = new ReflectionClass($model);
        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            throw new UnexpectedValueException('Class must implement "'.Model::class.'".');
        }

        $this->models = array_unique([...$this->models, $model]);
        unset($this->withoutModelFields[$model]);

        return $this;
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function withoutModel(string $model): self
    {
        $this->models = array_diff($this->models, [$model]);

        $this->withoutModelFields($model, ['*']);

        return $this;
    }

    /**
     * @param  class-string<Model>  $model
     * @return list<non-empty-string>
     */
    private function getModelFields(string $model): array
    {
        $skip = $this->withoutModelFields[$model] ?? [];

        if ($skip === ['*']) {
            return [];
        }

        /** @var array<non-empty-string, non-empty-string|class-string<Castable>> $fields */
        $fields = collect((fn (): array => $this->casts())->call(new $model))
            ->filter(fn (string $cast, string $field) => in_array($cast, $this->casts) && ! in_array($field, $skip))
            ->toArray();

        return array_keys($fields);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  non-empty-list<non-empty-string>  $fields
     */
    public function withoutModelFields(string $model, array $fields): self
    {
        $this->withoutModelFields[$model] = $fields;

        return $this;
    }
}
