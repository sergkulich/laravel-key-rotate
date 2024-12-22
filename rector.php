<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    /*->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])*/
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        //typeDeclarations: true,
        //privatization: true,
        //earlyReturn: true,
        //strictBooleans: true,
    )
    /*->withPhpSets(php82: true)
    ->withTypeCoverageLevel(0)*/;
