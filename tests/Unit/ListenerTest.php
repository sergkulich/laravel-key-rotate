<?php

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use SergKulich\LaravelKeyRotate\Events\KeyRotatedEvent;
use SergKulich\LaravelKeyRotate\Facades\KeyRotate;
use Workbench\App\Casts\CustomCast;
use Workbench\App\Invalid\InvalidCast;
use Workbench\App\Invalid\InvalidModel;
use Workbench\App\Models\Secret;

use function Orchestra\Testbench\workbench_path;

it('listening the event', function () {
    Event::fake([KeyRotatedEvent::class]);

    $listener = KeyRotate::withListener();

    Event::assertListening(KeyRotatedEvent::class, $listener);
});

it('extends casts', function () {
    $listener = KeyRotate::withListener();

    $listener->withoutCast('encrypted');
    expect('encrypted')->not->toBeIn($listener->getCasts());

    $listener->withCast('encrypted');
    expect('encrypted')->toBeIn($listener->getCasts());

    $listener->withCast(CustomCast::class);
    expect(CustomCast::class)->toBeIn($listener->getCasts());

    $listener->withoutCast(AsEncryptedArrayObject::class);
    expect(AsEncryptedArrayObject::class)->not->toBeIn($listener->getCasts());

    $fn = fn () => $listener->withCast('invalid-cast-name');
    expect($fn)->toThrow(UnexpectedValueException::class);

    $fn = fn () => $listener->withCast(InvalidCast::class);
    expect($fn)->toThrow(UnexpectedValueException::class);
});

it('extends and cuts dirs', function () {
    $listener = KeyRotate::withListener()
        ->withDir(workbench_path('app'), 'Workbench\\App\\')
        ->withDir(workbench_path('database'), 'Workbench\\Database\\');

    $dirs = $listener->getDirs();
    expect(count($dirs))->toBe(3)
        ->and(app()->path())->toBeIn(array_keys($dirs))
        ->and(app()->getNamespace())->toBeIn($dirs)
        ->and(workbench_path('app'))->toBeIn(array_keys($dirs))
        ->and('Workbench\\App\\')->toBeIn($dirs)
        ->and(workbench_path('database'))->toBeIn(array_keys($dirs))
        ->and('Workbench\\Database\\')->toBeIn($dirs);

    $listener->withoutDir(workbench_path('database'));

    $dirs = $listener->getDirs();
    expect(count($dirs))->toBe(2)
        ->and(workbench_path('database'))->not->toBeIn(array_keys($dirs))
        ->and('Workbench\\Database\\')->not->toBeIn($dirs);

    $fn = fn () => $listener->withDir(workbench_path('wrong-path'), 'Workbench\\WrongPath\\');
    expect($fn)->toThrow(UnexpectedValueException::class);
});

it('extends and cuts models', function () {
    $listener = KeyRotate::withListener();

    $listener->withModel(Secret::class);
    expect(Secret::class)->toBeIn($listener->getModels());

    $listener->withoutModel(Secret::class);
    expect(Secret::class)->not->toBeIn($listener->getModels());

    $fn = fn () => $listener->withModel('invalid-class-name');
    expect($fn)->toThrow(UnexpectedValueException::class);

    $fn = fn () => $listener->withModel(InvalidModel::class);
    expect($fn)->toThrow(UnexpectedValueException::class);
});

it('extends and cuts models fields', function () {
    $listener = KeyRotate::withListener()
        ->withModel(Secret::class)
        ->withoutModelFields(Secret::class, ['encrypted']);

    expect(Secret::class)->toBeIn($listener->getModels());

    $skip = (fn () => $this->withoutModelFields)->call($listener);
    expect(Secret::class)->toBeIn(array_keys($skip))
        ->and('encrypted')->toBeIn($skip[Secret::class]);

    $listener->withoutModel(Secret::class);

    $skip = (fn () => $this->withoutModelFields)->call($listener);
    expect(Secret::class)->toBeIn(array_keys($skip))
        ->and($skip[Secret::class])->toBe(['*']);
});
