<?php

namespace Burst\BurstPayment\BurstApi;

class BurstApiConfig
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getBurstAddress(): ?string
    {
        return $this->config['burstAddress'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getBurstWalletUrl(): ?string
    {
        return $this->config['burstWalletUrl'] ?? null;
    }
}
