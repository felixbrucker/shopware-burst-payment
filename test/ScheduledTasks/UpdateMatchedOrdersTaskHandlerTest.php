<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\ScheduledTasks;

use Burst\BurstPayment\ScheduledTasks\UpdateMatchedOrdersTask;
use Burst\BurstPayment\ScheduledTasks\UpdateMatchedOrdersTaskHandler;
use Burst\BurstPayment\Services\OpenOrdersService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @testdox UpdateMatchedOrdersTaskHandler
 */
class UpdateMatchedOrdersTaskHandlerTest extends TestCase
{
    /**
     * @var OpenOrdersService|MockObject
     */
    private $openOrdersServiceMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var UpdateMatchedOrdersTaskHandler
     */
    private $updateMatchedOrdersTaskHandler;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->openOrdersServiceMock = $this->createMock(OpenOrdersService::class);
        $scheduledTaskRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->updateMatchedOrdersTaskHandler = new UpdateMatchedOrdersTaskHandler(
            $scheduledTaskRepositoryMock,
            $this->openOrdersServiceMock,
            $this->loggerMock
        );
    }

    /**
     * @testdox calls the updateMatchedOrders method of the OpenOrdersService when running the task
     */
    public function test_run_callUpdateMatchedOrders(): void
    {
        $this->openOrdersServiceMock
            ->expects(self::once())
            ->method('updateMatchedOrders')
            ->with(Context::createDefaultContext());

        $this->updateMatchedOrdersTaskHandler->run();
    }

    /**
     * @testdox logs an error when running the task and an exception is thrown
     */
    public function test_run_logException(): void
    {
        $e = new \Exception('some error message');
        $this->openOrdersServiceMock
            ->method('updateMatchedOrders')
            ->willThrowException($e);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Update Matched Orders | Error: some error message',
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
        self::assertSame([ UpdateMatchedOrdersTask::class ], UpdateMatchedOrdersTaskHandler::getHandledMessages());
    }
}
