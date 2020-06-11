<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\BurstRate;

use Burst\BurstPayment\BurstRate\BurstRateEntityDefinition;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\BurstRate\CoinGeckoApi;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox BurstRateDependencyInjection
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

        $this->getContainer()->get(BurstRateEntityDefinition::class);
        $this->getContainer()->get(BurstRateService::class);
        $this->getContainer()->get(CoinGeckoApi::class);
    }
}
