<?php

namespace Burst\BurstPayment\BurstApi;

class BurstApiConfig
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getBurstAddress(): ?string
    {
        return $this->getConfigValueOrNull('burstAddress');
    }

    /**
     * @return string|null
     */
    public function getBurstWalletUrl(): ?string
    {
        return $this->getConfigValueOrNull('burstWalletUrl');
    }

    private function getConfigValueOrNull(string $configKey)
    {
        return $this->config[$configKey] ?? null;
    }
}
