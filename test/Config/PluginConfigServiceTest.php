<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Config;

use Burst\BurstPayment\Config\PluginConfig;
use Burst\BurstPayment\Config\PluginConfigService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @testdox PluginConfigService
 */
class PluginConfigServiceTest extends TestCase
{
    /**
     * @var MockObject|SystemConfigService
     */
    private $systemConfigServiceMock;

    /**
     * @var PluginConfigService
     */
    private $pluginConfigService;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->systemConfigServiceMock = $this->createMock(SystemConfigService::class);
        $this->pluginConfigService = new PluginConfigService($this->systemConfigServiceMock);
    }

    /**
     * @testdox passes the array retrieved from the system config service into the plugin config when retrieving the plugin config
     */
    public function test_getPluginConfigForSalesChannel_passSystemConfigIntoStripePluginConfig(): void
    {
        $this->systemConfigServiceMock->method('get')->willReturn([
            'burstAddress' => 'someValue',
        ]);

        $pluginConfig = $this->pluginConfigService->getPluginConfigForSalesChannel();

        self::assertEquals(new PluginConfig([
            'burstAddress' => 'someValue',
        ]), $pluginConfig);
    }

    /**
     * @testdox passes an empty array into the plugin config when retrieving the plugin config and the system config is null
     */
    public function test_getPluginConfigForSalesChannel_systemConfigNull(): void
    {
        $this->systemConfigServiceMock->method('get')->willReturn(null);

        $pluginConfig = $this->pluginConfigService->getPluginConfigForSalesChannel();

        self::assertEquals(new PluginConfig([]), $pluginConfig);
    }

    /**
     * @testdox passes the supplied sales channel id to the system config service when retrieving the plugin config
     */
    public function test_getPluginConfigForSalesChannel_usesSalesChannelId(): void
    {
        $this->systemConfigServiceMock
            ->expects(self::once())
            ->method('get')
            ->with(
                'BurstPayment.config',
                '12345'
            );

        $this->pluginConfigService->getPluginConfigForSalesChannel('12345');
    }
}
