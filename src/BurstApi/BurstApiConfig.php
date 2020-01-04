<?php

namespace Burst\BurstPayment\BurstApi;

class BurstApiConfig
{
    /** @var string */
    public $burstAddress;

    /** @var string */
    public $walletUrl;

    private function __construct() {}

    public static function fromShopwareConfig(array $config): BurstApiConfig
    {
        $self = new self();

        $self->burstAddress = $config['burstAddress'];
        $self->walletUrl = $config['burstWalletUrl'];

        return $self;
    }
}