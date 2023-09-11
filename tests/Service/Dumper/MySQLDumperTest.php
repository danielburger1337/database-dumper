<?php declare(strict_types=1);

namespace App\Tests\Service\Dumper;

use App\Service\Dumper\MySQLDumper;
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
        $this->getService()->dump($this->getDsn());

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName()));
    }

    public function testDumpOneDatabase(): void
    {
        $dsn = $this->getDsn();
        $dsn->path = 'mysql';

        $this->getService()->dump($dsn);

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName()));

        // ? Maybe somehow find out that only this db was actually dumped
    }

    public function testDumpMultipleDatabase(): void
    {
        $dsn = $this->getDsn();
        $dsn->path = 'mysql information_schema';

        $this->getService()->dump($dsn);

        $this->assertTrue($this->getFilesystem()->fileExists($this->getExpectedFileName()));

        // ? Maybe somehow find out that only this db was actually dumped
    }

    public function testCustomName(): void
    {
        $dsn = $this->getDsn();
        $dsn->options['filename'] = 'customName-{date}';

        $this->getService()->dump($dsn);

        $filename = 'customName-'.$this->getClock()->now()->format('Ymd_His').'.sql.gz';

        $this->assertTrue($this->getFilesystem()->fileExists($filename));
    }

    private function getClock(): ClockInterface
    {
        return static::getContainer()->get(ClockInterface::class);
    }

    private function getFilesystem(): FilesystemOperator
    {
        return static::getContainer()->get('app.default_storage');
    }

    private function getService(): MySQLDumper
    {
        return static::getContainer()->get(MySQLDumper::class);
    }

    private function getDsn(): Dsn
    {
        return Dsn::fromString($_ENV['DB_DUMPER_MYSQL_COMMAND']);
    }

    private function getExpectedFileName(): string
    {
        return 'mysqldump-'.$this->getClock()->now()->format('Ymd_His').'.sql.gz';
    }

    protected function setUp(): void
    {
        ClockMock::register(MySQLDumper::class);
    }
}
