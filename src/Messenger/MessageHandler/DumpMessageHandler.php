<?php declare(strict_types=1);

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\DumpMessage;
use App\Service\Dumper\DumperInterface;
use App\Util\Dsn;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler]
class DumpMessageHandler
{
    /**
     * @param iterable<DumperInterface> $dumperServices
     */
    public function __construct(
        #[TaggedIterator('app.dumper_service')]
        private readonly iterable $dumperServices,
        #[Autowire(env: 'DB_DUMPER_COMMAND')]
        private readonly string $command
    ) {
    }

    public function __invoke(DumpMessage $message): void
    {
        $dsn = Dsn::fromString($this->command);

        foreach ($this->dumperServices as $service) {
            if ($service->supports($dsn)) {
                try {
                    $service->dump($dsn);
                    break;
                } catch (\InvalidArgumentException) {
                    // noop
                } catch (ProcessFailedException $e) {
                    throw new UnrecoverableMessageHandlingException(previous: $e);
                }
            }
        }
    }
}
