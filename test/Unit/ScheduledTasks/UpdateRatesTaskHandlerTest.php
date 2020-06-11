<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\ScheduledTasks;

use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\ScheduledTasks\UpdateRatesTask;
use Burst\BurstPayment\ScheduledTasks\UpdateRatesTaskHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @testdox UpdateRatesTaskHandler
 */
class UpdateRatesTaskHandlerTest extends TestCase
{
    /**
     * @var BurstRateService|MockObject
     */
    private $burstRateServiceMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var UpdateRatesTaskHandler
     */
    private $updateMatchedOrdersTaskHandler;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->burstRateServiceMock = $this->createMock(BurstRateService::class);
        $scheduledTaskRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->updateMatchedOrdersTaskHandler = new UpdateRatesTaskHandler(
            $scheduledTaskRepositoryMock,
            $this->burstRateServiceMock,
            $this->loggerMock
        );
    }

    /**
     * @testdox calls the updateRates method of the BurstRateService when running the task
     */
    public function test_run_callUpdateRates(): void
    {
        $this->burstRateServiceMock
            ->expects(self::once())
            ->method('updateRates')
            ->with(Context::createDefaultContext());

        $this->updateMatchedOrdersTaskHandler->run();
    }

    /**
     * @testdox logs an error when running the task and an exception is thrown
     */
    public function test_run_logException(): void
    {
        $e = new \Exception('some error message');
        $this->burstRateServiceMock
            ->method('updateRates')
            ->willThrowException($e);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Update Rates | Error: some error message',
                [
                    'exception' => $e,
                    'stackTrace' => $e->getTraceAsString(),
                ]
            );

        $this->updateMatchedOrdersTaskHandler->run();
    }

    /**
     * @testdox returns the correct task class when retrieving the message to handle
     */
    public function test_getHandledMessages(): void
    {
        self::assertSame([ UpdateRatesTask::class ], UpdateRatesTaskHandler::getHandledMessages());
    }
}
