<?php declare(strict_types=1);

namespace Burst\BurstPayment\ScheduledTasks;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class UpdateRatesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'burst_payment.update_rates_task';
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultInterval(): int
    {
        return 10 * 60; // Run every 10 minutes
    }
}
