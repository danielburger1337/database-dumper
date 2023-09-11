<?php declare(strict_types=1);

namespace App\Dumper;

use App\Service\UploadService;
use App\Util\Dsn;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

class MySQLDumper implements DumperInterface
{
    final public const SCHEME = 'mysql';

    public function __construct(
        private readonly ClockInterface $clock,
        #[Autowire(env: 'MYSQLDUMP_BINARY')]
        private readonly string $binaryPath,
        #[Autowire(env: 'MYSQLDUMP_FILENAME')]
        private readonly string $fileName,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projcectDir,
        #[Autowire(env: 'bool:DB_DUMPER_ENABLE_GZIP')]
        private readonly bool $enableGzip,
        private readonly UploadService $uploadService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(Dsn $dsn): bool
    {
        return self::SCHEME === $dsn->getScheme();
    }

    public function dump(Dsn $dsn): void
    {
        $command = [
            $this->binaryPath,
            '-h', $dsn->getHost(),
            '-P', $dsn->getPort(3306),
            '-u', $dsn->getUser() ?? 'root',
        ];

        if (null !== $dsn->getPassword()) {
            $command[] = '--password='.$dsn->getPassword();
        }

        if (null !== $dsn->getPath()) {
            $command[] = '--databases';
            $command[] = \substr($dsn->getPath(), 1);
        } else {
            $command[] = '--all-databases';
        }

        $commandLine = (new Process($command))->getCommandLine();

        if ($this->enableGzip) {
            $commandLine .= ' | gzip -9';
        }

        $tmpFile = \sys_get_temp_dir().\DIRECTORY_SEPARATOR.\bin2hex(\random_bytes(16));

        if ($dsn->hasOption('timeout') && \is_numeric($dsn->getOption('timeout'))) {
            $timeout = (float) $dsn->getOption('timeout');
        } else {
            $timeout = 60;
        }

        $process = Process::fromShellCommandline($commandLine.' > "$OUTPUT_TARGET"', $this->projcectDir, timeout: $timeout);

        $this->logger->debug('[MySQLDumper] Executing command {command}', ['command' => $process->getCommandLine()]);

        try {
            $process->mustRun(null, [
                'OUTPUT_TARGET' => $tmpFile,
            ]);

            $filename = $this->createFileName(
                // @phpstan-ignore-next-line
                $dsn->hasOption('filename') ? (string) $dsn->getOption('filename') : $this->fileName,
            );

            $this->uploadService->uploadFile($filename, $tmpFile);
        } finally {
            @\unlink($tmpFile);
        }
    }

    private function createFileName(string $fileName): string
    {
        $fileName = \str_replace('{date}', $this->clock->now()->format('Ymd_His'), $fileName);
        $fileName .= '.sql';

        return $this->enableGzip ? $fileName.'.gz' : $fileName;
    }
}
