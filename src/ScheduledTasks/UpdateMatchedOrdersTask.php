<?php declare(strict_types=1);

namespace Burst\BurstPayment\ScheduledTasks;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class UpdateMatchedOrdersTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'burst_payment.update_matched_orders_task';
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultInterval(): int
    {
        return 30; // Run every 30 sec
    }
}
