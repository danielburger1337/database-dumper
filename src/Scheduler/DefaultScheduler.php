<?php declare(strict_types=1);

namespace App\Scheduler;

use App\Messenger\Message\CleanupMessage;
use App\Messenger\Message\DumpMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
class DefaultScheduler implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire(env: 'DB_DUMPER_SCHEDULE')]
        private readonly string $schedule,
        #[Autowire(env: 'DB_DUMPER_CLEANUP_SCHEDULE')]
        private readonly string $cleanupSchedule
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = (new Schedule())
            ->add(RecurringMessage::cron($this->schedule, new DumpMessage()))
        ;

        if ('' !== $this->cleanupSchedule) {
            $schedule->add(RecurringMessage::cron($this->cleanupSchedule, new CleanupMessage()));
        }

        return $schedule;
    }
}
