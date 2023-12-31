<?php declare(strict_types=1);

namespace App\Messenger\Message;

final readonly class CleanupMessage
{
    public function __construct(
        public bool $dryRun = false
    ) {
    }
}
