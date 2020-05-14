<?php declare(strict_types=1);

namespace Burst\BurstPayment\Config;

class PluginConfig
{
    /**
     * @var array
     */
    private $rawConfig;

    public function __construct(array $rawConfig)
    {
        $this->rawConfig = $rawConfig;
    }

    public function getBurstAddress(): ?string
    {
        return $this->getConfigValueOrNull('burstAddress');
    }

    public function getBurstWalletUrl(): ?string
    {
        return $this->getConfigValueOrNull('burstWalletUrl');
    }

    public function getRequiredConfirmationCount(): ?int
    {
        return $this->getConfigValueOrNull('requiredConfirmationCount');
    }

    public function getCancelUnmatchedOrdersAfterMinutes(): ?int
    {
        return $this->getConfigValueOrNull('cancelUnmatchedOrdersAfterMinutes');
    }

    private function getConfigValueOrNull(string $configKey)
    {
        return $this->rawConfig[$configKey] ?? null;
    }
}
