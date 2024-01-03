<?php declare(strict_types=1);

namespace App\Messenger\MessageHandler;

use App\Cleanup\CleanupAlgorithmInterface;
use App\Messenger\Message\CleanupMessage;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class CleanupMessageHandler
{
    /**
     * @param iterable<CleanupAlgorithmInterface> $cleanupAlgorithms
     */
    public function __construct(
        #[TaggedIterator('app.cleanup_algorithm')]
        private readonly iterable $cleanupAlgorithms,
        #[Autowire(env: 'DB_DUMPER_CLEANUP_ALGORITHM')]
        private readonly string $algorithm,
        private readonly FilesystemOperator $defaultStorage,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(CleanupMessage $message): void
    {
        foreach ($this->cleanupAlgorithms as $algo) {
            if ($this->algorithm === $algo->getName()) {
                $filePaths = $algo->getPathsToCleanup($this->defaultStorage);

                $this->logger->info('[Cleanup] Found {count} database dump to clean up.', ['count' => \count($filePaths), 'algorithm' => $algo->getName()]);

                if (!$message->dryRun) {
                    $algo->cleanup($filePaths, $this->defaultStorage);
                }

                return;
            }
        }

        throw new UnrecoverableMessageHandlingException('The "DB_DUMPER_CLEANUP_ALGORITHM" is not supported.');
    }
}
