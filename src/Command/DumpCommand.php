<?php declare(strict_types=1);

namespace App\Command;

use App\Messenger\Message\DumpMessage;
use App\Messenger\MessageHandler\DumpMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:dump', 'Dump the configured database.')]
class DumpCommand extends Command
{
    public function __construct(
        private readonly DumpMessageHandler $dumpMessageHandler
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dumpMessageHandler->__invoke(new DumpMessage());

        return Command::SUCCESS;
    }
}
