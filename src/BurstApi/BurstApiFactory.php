<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstApi;

use Burst\BurstPayment\Config\PluginConfigService;
use Psr\Log\LoggerInterface;

class BurstApiFactory
{
    /**
     * @var PluginConfigService
     */
    private $pluginConfigService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PluginConfigService $pluginConfigService,
        LoggerInterface $logger
    ) {
        $this->pluginConfigService = $pluginConfigService;
        $this->logger = $logger;
    }

    public function createBurstApiForSalesChannel(?string $salesChannelId = null): BurstApi
    {
        $pluginConfig = $this->pluginConfigService->getPluginConfigForSalesChannel($salesChannelId);

        return new BurstApi($pluginConfig, $this->logger);
    }
}
