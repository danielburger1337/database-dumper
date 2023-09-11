<?php declare(strict_types=1);

namespace App\Cleanup;

use League\Flysystem\FilesystemOperator;

interface CleanupAlgorithmInterface
{
    public function getName(): string;

    public function cleanup(FilesystemOperator $filesystem): void;
}
