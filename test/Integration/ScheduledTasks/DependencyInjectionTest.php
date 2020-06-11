<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\ScheduledTasks;

use Burst\BurstPayment\ScheduledTasks\MatchUnmatchedOrdersTask;
use Burst\BurstPayment\ScheduledTasks\MatchUnmatchedOrdersTaskHandler;
use Burst\BurstPayment\ScheduledTasks\UpdateMatchedOrdersTask;
use Burst\BurstPayment\ScheduledTasks\UpdateMatchedOrdersTaskHandler;
use Burst\BurstPayment\ScheduledTasks\UpdateRatesTask;
use Burst\BurstPayment\ScheduledTasks\UpdateRatesTaskHandler;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox ScheduledTasksDependencyInjection
 */
class DependencyInjectionTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @testdox can load all services
     */
    public function test_canLoadServices(): void
    {
        $this->addToAssertionCount(1);

        $this->getContainer()->get(UpdateRatesTask::class);
        $this->getContainer()->get(UpdateRatesTaskHandler::class);
        $this->getContainer()->get(MatchUnmatchedOrdersTask::class);
        $this->getContainer()->get(MatchUnmatchedOrdersTaskHandler::class);
        $this->getContainer()->get(UpdateMatchedOrdersTask::class);
        $this->getContainer()->get(UpdateMatchedOrdersTaskHandler::class);
    }
}
