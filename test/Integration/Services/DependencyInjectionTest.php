<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\Services;

use Burst\BurstPayment\Services\OpenOrdersService;
use Burst\BurstPayment\Services\OrderTransactionService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox ServicesDependencyInjection
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

        $this->getContainer()->get(OrderTransactionService::class);
        $this->getContainer()->get(OpenOrdersService::class);
    }
}
