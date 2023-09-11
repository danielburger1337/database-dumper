<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\FilesystemOperator;

interface FilesystemFactoryInterface
{
    /**
     * Whether the filesystem factory supports the given DSN.
     *
     * @param Dsn $dsn The DSN to check.
     */
    public function supports(Dsn $dsn): bool;

    /**
     * Create a filesystem from the given DSN.
     *
     * @param Dsn $dsn The DSN to create the filesystem from.
     */
    public function createFilesystem(Dsn $dsn): FilesystemOperator;
}
