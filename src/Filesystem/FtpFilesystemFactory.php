<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class FtpFilesystemFactory implements FilesystemFactoryInterface
{
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'ftp' || $dsn->getScheme() === 'ftps';
    }

    public function createFilesystem(Dsn $dsn): FilesystemOperator
    {
        if (null === $dsn->getUser()) {
            throw new \InvalidArgumentException('A "user" is required to create a FTP filesystem.');
        }

        $providerOptions = [
            'host' => $dsn->getHost(),
            'username' => $dsn->getUser(),
            'port' => $dsn->getPort(21),
            'timeout' => (int) $dsn->getOption('timeout', 5),
            'ssl' => 'ftps' === $dsn->getScheme(),
            'root' => $dsn->getPath() ?? '/',
        ];

        if (null !== ($password = $dsn->getPassword())) {
            $providerOptions['password'] = $password;
        }

        $connectionProvider = FtpConnectionOptions::fromArray($providerOptions);

        return new Filesystem(new FtpAdapter($connectionProvider));
    }
}
