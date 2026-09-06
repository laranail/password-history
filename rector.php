<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;

return RectorConfig::configure()
    ->withCache(
        cacheDirectory: './.cache/rector',
        cacheClass: FileCacheStorage::class,
        containerCacheDirectory: './.cache/rectorContainer',
    )
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true);
