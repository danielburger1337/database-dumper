<?php declare(strict_types=1);

namespace App\Cleanup;

use League\Flysystem\FilesystemOperator;

interface CleanupAlgorithmInterface
{
    /**
     * The unique name of the cleanup algorithm.
     */
    public function getName(): string;

    /**
     * The paths that should be cleaned up.
     *
     * @return string[]
     */
    public function getPathsToCleanup(FilesystemOperator $filesystem): array;

    /**
     * Cleanup the given file paths.
     *
     * @param string[] $filePaths The file paths to cleanup.
     */
    public function cleanup(array $filePaths, FilesystemOperator $filesystem): void;
}
