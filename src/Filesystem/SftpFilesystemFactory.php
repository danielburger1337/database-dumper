<?php declare(strict_types=1);

namespace App\Filesystem;

use App\Util\Dsn;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

class SftpFilesystemFactory implements FilesystemFactoryInterface
{
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'sftp';
    }

    public function createFilesystem(Dsn $dsn): FilesystemOperator
    {
        if (null === $dsn->getUser()) {
            throw new \InvalidArgumentException('A "user" is required to create a SFTP filesystem.');
        }

        $providerOptions = [
            'host' => $dsn->getHost(),
            'username' => $dsn->getUser(),
            'port' => $dsn->getPort(22),
            'timeout' => (int) $dsn->getOption('timeout', 5),
        ];

        if (null !== ($hostFingerprint = $dsn->getOption('hostFingerprint'))) {
            $providerOptions['hostFingerprint'] = $hostFingerprint;
        }

        if (null !== ($privateKey = $dsn->getOption('base64_privateKey'))) {
            $providerOptions['privateKey'] = \base64_decode((string) $privateKey);
        } else {
            if (null !== ($password = $dsn->getPassword())) {
                $providerOptions['password'] = $password;
            }
        }

        $connectionProvider = SftpConnectionProvider::fromArray($providerOptions);

        return new Filesystem(new SftpAdapter($connectionProvider, $dsn->getPath() ?? '/'));
    }
}
