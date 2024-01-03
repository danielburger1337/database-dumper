<?php declare(strict_types=1);

namespace App\Cleanup;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
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

    public function cleanup(FilesystemOperator $filesystem): void
    {
        $directories = $filesystem->listContents('.')
            ->filter(static fn (StorageAttributes $attr): bool => $attr->isDir())
            ->map(static fn (StorageAttributes $attr): string => $attr->path())
        ;

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

            $this->logger->info('[Cleanup] The storage destination "{directory}" has {count} sql dumps.', ['directory' => $directory, 'count' => $count]);

            if ($count <= $this->retentionCount) {
                $this->logger->info('[Cleanup] No dumps from "{directory}" will be removed because retention count of {retentionCount} is not exceeded.', ['directory' => $directory, 'retentionCount' => $this->retentionCount]);

                continue;
            }

            $ordered = [];
            foreach ($paths as $path) {
                $ordered[$filesystem->lastModified($path)] = $path;
            }

            \ksort($ordered, \SORT_NUMERIC);
            $ordered = \array_reverse($ordered);

            $toDelete = \array_slice($ordered, $this->retentionCount);

            foreach ($toDelete as $path) {
                $this->logger->info('[Cleanup] Removing old sql dump "{path}".', ['path' => $path]);

                $filesystem->delete($path);
            }
        }
    }
}
