<?php declare(strict_types=1);

namespace App\Messenger\MessageHandler;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CleanupMessageHandler
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        #[Autowire(env: 'int:DB_DUMPER_CLEANUP_RETENTION_COUNT')]
        private readonly int $retentionCount,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(): void
    {
        $paths = $this->defaultStorage->listContents('.')
            ->filter(fn (StorageAttributes $attr) => $attr->isFile())
            ->filter(function (StorageAttributes $attr): bool {
                $fileName = \pathinfo($attr->path(), \PATHINFO_BASENAME);

                return \str_ends_with($fileName, '.sql') || \str_ends_with($fileName, '.sql.gz');
            })
            ->map(fn (StorageAttributes $attr) => $attr->path())
            ->toArray()
        ;

        $count = \count($paths);

        $this->logger->debug('[Cleanup] The storage destination has {count} sql dumps.', ['count' => $count]);

        if ($count <= $this->retentionCount) {
            return;
        }

        $ordered = [];
        foreach ($paths as $path) {
            $ordered[$this->defaultStorage->lastModified($path)] = $path;
        }

        \ksort($ordered, \SORT_NUMERIC);
        $ordered = \array_reverse($ordered);

        $toDelete = \array_slice($ordered, $this->retentionCount);

        foreach ($toDelete as $path) {
            $this->logger->debug('[Cleanup] Removing old sql dump "{path}".', ['path' => $path]);

            $this->defaultStorage->delete($path);
        }
    }
}
