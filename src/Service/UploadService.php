<?php declare(strict_types=1);

namespace App\Service;

use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

class UploadService
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'DB_DUMPER_ENCRYPTION_PASSWORD')]
        private readonly string $encryptionPassword
    ) {
    }

    public function uploadFile(string $path, string $localPath): void
    {
        if ('' !== $this->encryptionPassword) {
            $process = new Process([
                'openssl',
                'enc', '-aes-256-cbc', '-pbkdf2',
                '-in', $localPath,
                '-out', $localPath.'.enc',
                '-k', $this->encryptionPassword,
            ]);

            $process->mustRun();

            $this->logger->info('Encrypting dump "{fileName}".', ['fileName' => $localPath]);

            $localPath .= '.enc';
            $path .= '.enc';

            $unlink = true;
        } else {
            $unlink = false;
        }

        $handle = \fopen($localPath, 'r+');
        if (false === $handle) {
            throw new \RuntimeException("Failed to open file handle for {$localPath}");
        }

        $this->logger->info('Uploading dump "{fileName}".', ['fileName' => $path]);

        try {
            $this->defaultStorage->writeStream($path, $handle);
        } finally {
            \fclose($handle);
            $unlink && @\unlink($localPath);
        }
    }
}
