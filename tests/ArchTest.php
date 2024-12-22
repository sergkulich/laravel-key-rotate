<?php

arch()->preset()->php();
arch()->preset()->security();

arch()->expect(['dd'])->not->toBeUsed();

arch()
    ->expect('SergKulich\LaravelKeyRotate')
    ->not->toBeAbstract()
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->toBeFinal();
