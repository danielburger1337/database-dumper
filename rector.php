<?php declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->symfonyContainerXml(__DIR__.'/var/cache/test/App_KernelTestDebugContainer.xml');
    $rectorConfig->symfonyContainerPhp(__DIR__.'/tests/symfony-container.php');

    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon');

    $rectorConfig->removeUnusedImports();

    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ReadOnlyPropertyRector::class,
        ClassConstantToSelfClassRector::class,
        JsonThrowOnErrorRector::class,
        FinalizePublicClassConstantRector::class,
        NullToStrictStringFuncCallArgRector::class,
    ]);

    // define sets of rules
    $rectorConfig->sets([
        // LevelSetList::UP_TO_PHP_82,
    ]);
};
