<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\Logging;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox LoggingDependencyInjection
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

        $this->getContainer()->get('burst_payment.logger');
    }
}
