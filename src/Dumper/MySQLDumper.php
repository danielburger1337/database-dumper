<?php declare(strict_types=1);

namespace App\Dumper;

use App\Service\UploadService;
use App\Util\Dsn;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

class MySQLDumper implements DumperInterface
{
    final public const SCHEME = 'mysql';

    private const DEFAULT_DATABASE_NAMES = ['mysql', 'information_schema', 'performance_schema', 'sys'];

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
        if (null === $dsn->getPath() || '/' === $dsn->getPath()) {
            $databaseNames = $this->findDatabaseNames($dsn);

            if ($dsn->getOption('includeDefaultDbs', 'false') === 'false') {
                foreach ($databaseNames as $key => $db) {
                    if (\in_array($db, self::DEFAULT_DATABASE_NAMES, true)) {
                        unset($databaseNames[$key]);
                    }
                }
            }
        } else {
            $path = $dsn->getPath();
            if (\str_starts_with($path, '/')) {
                $path = \substr($dsn->getPath(), 1);
            }

            $databaseNames = \explode(',', $path);

            unset($path);
        }

        foreach ($databaseNames as $databaseName) {
            $this->logger->debug('[MySQLDumper] Dumping database "{database}', ['database' => $databaseName]);

            $this->dumpDatabase($dsn, $databaseName);
        }
    }

    /**
     * @param string $database The name of the database to dump.
     */
    private function dumpDatabase(Dsn $dsn, string $database): void
    {
        $command = [
            $this->binaryPath,
            '-h', $dsn->getHost(),
            '-P', $dsn->getPort(3306),
            '-u', $dsn->getUser() ?? 'root',
            '--databases', $database,
        ];

        if (null !== $dsn->getPassword()) {
            $command[] = '--password='.$dsn->getPassword();
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

        $commandToLog = $commandLine;
        if (null !== $dsn->getPassword()) {
            $commandToLog = \str_replace($dsn->getPassword(), '*****', $commandToLog);
        }

        $this->logger->debug('[MySQLDumper] Executing command {command}"', ['command' => $commandToLog]);

        try {
            $process->mustRun(null, [
                'OUTPUT_TARGET' => $tmpFile,
            ]);

            $this->uploadService->uploadFile($database.'/'.$this->createFileName($database), $tmpFile);
        } finally {
            @\unlink($tmpFile);
        }
    }

    private function createFileName(string $dbName): string
    {
        $fileName = \str_replace('{dbname}', $dbName, $this->fileName);
        $fileName = \str_replace('{date}', $this->clock->now()->format('Ymd_His'), $fileName);
        $fileName .= '.sql';

        return $this->enableGzip ? $fileName.'.gz' : $fileName;
    }

    private function findDatabaseNames(Dsn $dsn): array
    {
        $connectionParams = [
            'user' => $dsn->getUser() ?? 'root',
            'host' => $dsn->getHost(),
            'port' => (int) $dsn->getPort(3306),
            'driver' => 'pdo_mysql',
        ];

        if (null !== $dsn->getPassword()) {
            $connectionParams['password'] = $dsn->getPassword();
        }

        $conn = DriverManager::getConnection($connectionParams);

        $stmt = $conn->prepare('SHOW DATABASES;');

        $result = $stmt->executeQuery()->fetchAllAssociativeIndexed();

        return \array_keys($result);
    }
}
