<?php

namespace Burst\BurstPayment\ScheduledTasks;

use Burst\BurstPayment\Services\OpenOrdersService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class UpdateMatchedOrdersTaskHandler extends ScheduledTaskHandler
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
        return [ UpdateMatchedOrdersTask::class ];
    }

    public function run(): void
    {
        try {
            $this->openOrdersService->updateMatchedOrders();
        } catch (\Throwable $e) {
            $this->logger->error('[Burst Payment] [Update matched Orders Task] Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
