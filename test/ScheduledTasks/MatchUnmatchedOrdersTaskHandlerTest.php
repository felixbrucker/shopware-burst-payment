<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\ScheduledTasks;

use Burst\BurstPayment\ScheduledTasks\MatchUnmatchedOrdersTask;
use Burst\BurstPayment\ScheduledTasks\MatchUnmatchedOrdersTaskHandler;
use Burst\BurstPayment\Services\OpenOrdersService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @testdox MatchUnmatchedOrdersTaskHandler
 */
class MatchUnmatchedOrdersTaskHandlerTest extends TestCase
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
     * @var MatchUnmatchedOrdersTaskHandler
     */
    private $matchUnmatchedOrdersTaskHandler;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->openOrdersServiceMock = $this->createMock(OpenOrdersService::class);
        $scheduledTaskRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->matchUnmatchedOrdersTaskHandler = new MatchUnmatchedOrdersTaskHandler(
            $scheduledTaskRepositoryMock,
            $this->openOrdersServiceMock,
            $this->loggerMock
        );
    }

    /**
     * @testdox calls the matchUnmatchedOrders method of the OpenOrdersService when running the task
     */
    public function test_run_callMatchUnmatchedOrders(): void
    {
        $this->openOrdersServiceMock
            ->expects(self::once())
            ->method('matchUnmatchedOrders')
            ->with(Context::createDefaultContext());

        $this->matchUnmatchedOrdersTaskHandler->run();
    }

    /**
     * @testdox logs an error when running the task and an exception is thrown
     */
    public function test_run_logException(): void
    {
        $e = new \Exception('some error message');
        $this->openOrdersServiceMock
            ->method('matchUnmatchedOrders')
            ->willThrowException($e);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Match Unmatched Orders | Error: some error message',
                [
                    'exception' => $e,
                    'stackTrace' => $e->getTraceAsString(),
                ]
            );

        $this->matchUnmatchedOrdersTaskHandler->run();
    }

    /**
     * @testdox returns the correct task class when retrieving the message to handle
     */
    public function test_getHandledMessages(): void
    {
        self::assertSame([ MatchUnmatchedOrdersTask::class ], MatchUnmatchedOrdersTaskHandler::getHandledMessages());
    }
}
