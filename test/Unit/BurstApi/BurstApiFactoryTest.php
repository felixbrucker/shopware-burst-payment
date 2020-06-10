<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\BurstApi;

use Burst\BurstPayment\BurstApi\BurstApi;
use Burst\BurstPayment\BurstApi\BurstApiFactory;
use Burst\BurstPayment\Config\PluginConfig;
use Burst\BurstPayment\Config\PluginConfigService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @testdox BurstApiFactory
 */
class BurstApiFactoryTest extends TestCase
{
    /**
     * @var PluginConfigService|MockObject
     */
    private $pluginConfigServiceMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var BurstApiFactory
     */
    private $burstApiFactory;

    /**
     * @var PluginConfig
     */
    private $pluginConfig;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->pluginConfigServiceMock = $this->createMock(PluginConfigService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->burstApiFactory = new BurstApiFactory(
            $this->pluginConfigServiceMock,
            $this->loggerMock
        );
        $this->pluginConfig = new PluginConfig([
            'burstAddress' => 'some address',
            'burstWalletUrl' => 'some wallet url',
        ]);
        $this->pluginConfigServiceMock
            ->method('getPluginConfigForSalesChannel')
            ->willReturnCallback(function () {
                return $this->pluginConfig;
            });
    }

    /**
     * @testdox passes the supplied sales channel id to the plugin config service when creating the burst api for a sales channel
     */
    public function test_createBurstApiForSalesChannel_fetchPluginConfigForSalesChannel(): void
    {
        $this->pluginConfigServiceMock
            ->expects(self::once())
            ->method('getPluginConfigForSalesChannel')
            ->with('12345');

        $this->burstApiFactory->createBurstApiForSalesChannel('12345');
    }

    /**
     * @testdox uses the plugin config obtained from the plugin config service when creating the burst api for a sales channel
     */
    public function test_createBurstApiForSalesChannel_usePluginConfig(): void
    {
        $this->pluginConfig = new PluginConfig([
           'burstAddress' => 'some other address',
           'burstWalletUrl' => 'some other wallet url',
        ]);

        $burstApi = $this->burstApiFactory->createBurstApiForSalesChannel();

        self::assertEquals(
            new BurstApi(new PluginConfig([
                'burstAddress' => 'some other address',
                'burstWalletUrl' => 'some other wallet url',
            ]), $this->loggerMock),
            $burstApi
        );
    }
}
