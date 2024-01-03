<?php declare(strict_types=1);

namespace App\Cleanup;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class KeepLatestCleanupAlgorithm implements CleanupAlgorithmInterface
{
    final public const NAME = 'keep_latest';

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'int:DB_DUMPER_CLEANUP_KEEP_LATEST_COUNT')]
        private readonly int $retentionCount,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPathsToCleanup(FilesystemOperator $filesystem): array
    {
        $directories = $filesystem->listContents('.')
            ->filter(static fn (StorageAttributes $attr): bool => $attr->isDir())
            ->map(static fn (StorageAttributes $attr): string => $attr->path())
        ;

        $cleanupPaths = [];

        foreach ($directories as $directory) {
            $paths = $filesystem->listContents($directory)
                ->filter(static fn (StorageAttributes $attr): bool => $attr->isFile())
                ->filter(static function (StorageAttributes $attr): bool {
                    $fileName = \pathinfo($attr->path(), \PATHINFO_BASENAME);

                    foreach (['.sql', '.sql.gz', '.enc'] as $ext) {
                        if (\str_ends_with($fileName, $ext)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->map(static fn (StorageAttributes $attr): string => $attr->path())
                ->toArray()
            ;

            $count = \count($paths);

            $this->log('The storage destination "{directory}" has {count} sql dumps.', ['directory' => $directory, 'count' => $count]);

            if ($count <= $this->retentionCount) {
                $this->log('No dumps from "{directory}" will be removed because retention count of {retentionCount} is not exceeded.', ['directory' => $directory, 'retentionCount' => $this->retentionCount]);

                continue;
            }

            $ordered = [];
            foreach ($paths as $path) {
                $ordered[$filesystem->lastModified($path)] = $path;
            }

            \ksort($ordered, \SORT_NUMERIC);
            $ordered = \array_reverse($ordered);

            $cleanupPaths = \array_merge($cleanupPaths, \array_slice($ordered, $this->retentionCount));
        }

        return \array_unique($cleanupPaths);
    }

    public function cleanup(array $filePaths, FilesystemOperator $filesystem): void
    {
        foreach ($filePaths as $path) {
            $this->log('Removing old sql dump "{path}".', ['path' => $path]);

            $filesystem->delete($path);
        }
    }

    private function log(string $message, array $context = [], string $level = LogLevel::INFO): void
    {
        $context['algorithm'] = $this->getName();

        $this->logger->log($level, '[Cleanup] '.$message, $context);
    }
}
