<?php

namespace Burst\BurstPayment\Services;

use Burst\BurstPayment\BurstApi\BurstApi;
use Burst\BurstPayment\BurstApi\BurstApiConfig;
use Psr\Log\LoggerInterface;

class BurstApiFactory
{
    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var array */
    private $burstApiPerSalesChannelId = [];

    public function __construct(
        SettingsService $settingsService,
        LoggerInterface $logger
    ) {
        $this->settingsService = $settingsService;
        $this->logger = $logger;
    }

    public function getBurstApiForSalesChannel(string $salesChannelId = null): BurstApi
    {
        if (isset($this->burstApiPerSalesChannelId[$salesChannelId])) {
            return $this->burstApiPerSalesChannelId[$salesChannelId];
        }

        $burstConfig = $this->settingsService->getConfig($salesChannelId);
        $burstApiConfig = BurstApiConfig::fromShopwareConfig($burstConfig);

        $this->burstApiPerSalesChannelId[$salesChannelId] = new BurstApi($burstApiConfig, $this->logger);

        return $this->burstApiPerSalesChannelId[$salesChannelId];
    }
}
