<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\Config;

use Burst\BurstPayment\Config\PluginConfigService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox ConfigDependencyInjection
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

        $this->getContainer()->get(PluginConfigService::class);
    }
}
