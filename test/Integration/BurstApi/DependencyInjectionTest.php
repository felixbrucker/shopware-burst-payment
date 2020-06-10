<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\BurstApi;

use Burst\BurstPayment\BurstApi\BurstApiController;
use Burst\BurstPayment\BurstApi\BurstApiFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox BurstApiDependencyInjection
 */
class DependencyInjectionTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @testdox can load all services
     */
    public function test_canLoadServices(): void
    {
        $this->getContainer()->get(BurstApiFactory::class);
        $this->getContainer()->get(BurstApiController::class);
    }
}
