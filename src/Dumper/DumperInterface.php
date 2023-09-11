<?php declare(strict_types=1);

namespace App\Dumper;

use App\Util\Dsn;

interface DumperInterface
{
    /**
     * Whether the dumping service supports the DSN.
     *
     * @param Dsn $dsn The DSN to check.
     */
    public function supports(Dsn $dsn): bool;

    /**
     * Dump the database specified by the given DSN.
     *
     * @param DSN $dsn The DSN to dump.
     */
    public function dump(Dsn $dsn): void;
}
