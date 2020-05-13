<?php

namespace Burst\BurstPayment\ScheduledTasks;

use Burst\BurstPayment\BurstRate\BurstRateService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class UpdateRatesTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var BurstRateService
     */
    private $burstRateService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        BurstRateService $burstRateService,
        LoggerInterface $logger
    ) {
        $this->burstRateService = $burstRateService;
        $this->logger = $logger;

        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [ UpdateRatesTask::class ];
    }

    public function run(): void
    {
        try {
            $this->burstRateService->updateRates();
        } catch (\Throwable $e) {
            $this->logger->error('Update Rates | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
