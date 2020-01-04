<?php

namespace Burst\BurstPayment\ScheduledTasks;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class MatchUnmatchedOrdersTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'burst_payment.match_unmatched_orders_task';
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultInterval(): int
    {
        return 60; // Run every minute
    }
}
