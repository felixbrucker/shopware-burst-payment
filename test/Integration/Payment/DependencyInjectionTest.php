<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\Payment;

use Burst\BurstPayment\Payment\CheckoutSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox PaymentDependencyInjection
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

        $this->getContainer()->get('burst_payment.payment_handler');
        $this->getContainer()->get(CheckoutSubscriber::class);
    }
}
