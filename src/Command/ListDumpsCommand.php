<?php declare(strict_types=1);

namespace App\Command;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:list-dumps', 'List all existing database dumps.')]
class ListDumpsCommand extends Command
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directories = $this->defaultStorage->listContents('.')
            ->filter(static fn (StorageAttributes $attr): bool => $attr->isDir())
            ->map(static fn (StorageAttributes $attr): string => $attr->path())
        ;

        $tableData = [];
        $count = 0;

        foreach ($directories as $directory) {
            /** @var FileAttributes[] */
            $paths = $this->defaultStorage->listContents($directory)
                ->filter(static fn (StorageAttributes $attr): bool => $attr->isFile())
                ->filter(static function (StorageAttributes $attr): bool {
                    $fileName = \pathinfo($attr->path(), \PATHINFO_BASENAME);

                    foreach (['.sql', '.sql.gz', '.enc'] as $ext) {
                        if (\str_ends_with($fileName, $ext)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->toArray()
            ;

            $count += \count($paths);

            $tableData[$directory] = $paths;
        }

        $outputData = [];

        foreach ($tableData as $dir => $files) {
            $i = 1;
            foreach ($files as $file) {
                $date = new \DateTimeImmutable('@'.$file->lastModified());

                $outputData[] = [$i, $file->path(), self::formatBytes($file->fileSize() ?? 0), $date->format(\DateTimeInterface::RFC1123)];
                ++$i;
            }

            $outputData[] = new TableSeparator();
        }

        \array_pop($outputData);

        $table = new Table($output);
        $table->setHeaders(['x', 'Path', 'Filesize', 'Last Modified At'])
            ->setRows($outputData)
            ->setHeaderTitle('Database Dumps')
            ->setFooterTitle($count.' dumps in total')
            ->setStyle('box')
        ;

        $table->render();

        return Command::SUCCESS;
    }

    private static function formatBytes(int $size, int $precision = 2): string
    {
        $base = \log($size, 1024);
        $suffixes = ['', 'KB', 'MB', 'GB', 'TB'];

        return \round(\pow(1024, $base - \floor($base)), $precision).' '.$suffixes[\floor($base)];
    }
}
