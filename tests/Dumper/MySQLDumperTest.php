<?php declare(strict_types=1);

namespace App\Tests\Dumper;

use App\Dumper\MySQLDumper;
use App\Util\Dsn;
use League\Flysystem\FilesystemOperator;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;

class MySQLDumperTest extends KernelTestCase
{
    public function testSupports(): void
    {
        $dsn = $this->getDsn();

        $service = $this->getService();

        $this->assertTrue($service->supports($dsn));

        $dsn = Dsn::fromString('invalid://mariadb');

        $this->assertFalse($service->supports($dsn));
    }

    public function testDumpAllDatabases(): void
    {
        $dsn = $this->getDsn();
        $dsn->path = null;

        $this->getService()->dump($dsn);

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName('db_dump_test')));
    }

    public function testDumpOneDatabase(): void
    {
        $dsn = $this->getDsn();
        $dsn->path = 'mysql';

        $this->getService()->dump($dsn);

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName('mysql')));
    }

    public function testDumpMultipleDatabase(): void
    {
        $dsn = $this->getDsn();
        $dsn->path = 'mysql,db_dump_test';

        $this->getService()->dump($dsn);

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName('mysql')));
        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName('db_dump_test')));
    }

    private function getClock(): ClockInterface
    {
        return static::getContainer()->get(ClockInterface::class); // @phpstan-ignore-line
    }

    private function getFilesystem(): FilesystemOperator
    {
        return static::getContainer()->get('app.default_storage'); // @phpstan-ignore-line
    }

    private function getService(): MySQLDumper
    {
        return static::getContainer()->get(MySQLDumper::class); // @phpstan-ignore-line
    }

    private function getDsn(): Dsn
    {
        return Dsn::fromString($_ENV['DB_DUMPER_COMMAND']);
    }

    private function getExpectedFileName(string $databaseName): string
    {
        return $databaseName.'/mysqldump-'.$databaseName.'-'.$this->getClock()->now()->format('Ymd_His').'.sql.gz';
    }

    protected function setUp(): void
    {
        ClockMock::register(MySQLDumper::class);
    }
}
