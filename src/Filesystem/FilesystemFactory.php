<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class FilesystemFactory
{
    /**
     * @param iterable<FilesystemFactoryInterface> $filesystemFactories
     */
    public function __construct(
        #[Autowire(env: 'DB_DUMPER_FILESYSTEM_DSN')]
        private readonly string $dsn,
        #[TaggedIterator('app.filesystem_factory')]
        private readonly iterable $filesystemFactories
    ) {
    }

    public function createFilesystem(): FilesystemOperator
    {
        $dsn = Dsn::fromString($this->dsn);

        foreach ($this->filesystemFactories as $factory) {
            try {
                if ($factory->supports($dsn)) {
                    return $factory->createFilesystem($dsn);
                }
            } catch (\InvalidArgumentException) {
            }
        }

        throw new \InvalidArgumentException('No FilesystemFactory supports the configured DSN.');
    }
}
