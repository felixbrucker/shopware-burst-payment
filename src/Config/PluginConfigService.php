<?php declare(strict_types=1);

namespace Burst\BurstPayment\Config;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginConfigService
{
    private const CONFIG_KEY = 'BurstPayment.config';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getPluginConfigForSalesChannel(?string $salesChannelId = null): PluginConfig
    {
        $rawConfig = $this->systemConfigService->get(self::CONFIG_KEY, $salesChannelId);

        return new PluginConfig($rawConfig);
    }
}
