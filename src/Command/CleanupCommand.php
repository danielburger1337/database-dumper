<?php declare(strict_types=1);

namespace App\Command;

use App\Messenger\Message\CleanupMessage;
use App\Messenger\MessageHandler\CleanupMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:cleanup', 'Cleanup the old database dump files.')]
class CleanupCommand extends Command
{
    public function __construct(
        private readonly CleanupMessageHandler $cleanupMessageHandler
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cleanupMessageHandler->__invoke(new CleanupMessage());

        return Command::SUCCESS;
    }
}
