<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemFactory implements FilesystemFactoryInterface
{
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

        return new Filesystem(new LocalFilesystemAdapter($path));
    }
}
