<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LocalFilesystemFactory implements FilesystemFactoryInterface
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $kernelProjectDir
    ) {
    }

    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'local' && $dsn->getHost() === 'local';
    }

    public function createFilesystem(Dsn $dsn): FilesystemOperator
    {
        $path = $dsn->getPath();

        if (null === $path) {
            throw new \InvalidArgumentException('A "path" is required to create a local filesystem.');
        }

        $path = \str_replace('%kernel.project_dir%', $this->kernelProjectDir, $path);
        if (\str_starts_with($path, '//')) {
            $path = \substr($path, 1);
        }

        return new Filesystem(new LocalFilesystemAdapter($path));
    }
}
