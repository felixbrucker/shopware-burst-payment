<?php

namespace Burst\BurstPayment\ScheduledTasks;

use Burst\BurstPayment\Services\OpenOrdersService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class MatchUnmatchedOrdersTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var OpenOrdersService
     */
    private $openOrdersService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        OpenOrdersService $openOrdersService,
        LoggerInterface $logger
    ) {
        $this->openOrdersService = $openOrdersService;
        $this->logger = $logger;

        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [ MatchUnmatchedOrdersTask::class ];
    }

    public function run(): void
    {
        try {
            $this->openOrdersService->matchUnmatchedOrders();
        } catch (\Throwable $e) {
            $this->logger->error('Match Unmatched Orders | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
