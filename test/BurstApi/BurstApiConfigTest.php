<?php

namespace Burst\BurstPayment\Test\BurstApi;

use Burst\BurstPayment\BurstApi\BurstApiConfig;
use PHPUnit\Framework\TestCase;

/**
 * @testdox BurstApiConfig
 */
class BurstApiConfigTest extends TestCase
{
    /**
     * @testdox returns the config values when the config value is set
     */
    public function test_configValueSet(): void
    {
        $burstApiConfig = new BurstApiConfig([
            'burstAddress' => 'someAddress',
            'burstWalletUrl' => 'someWalletUrl',
        ]);

        self::assertEquals('someAddress', $burstApiConfig->getBurstAddress());
        self::assertEquals('someWalletUrl', $burstApiConfig->getBurstWalletUrl());
    }

    /**
     * @testdox returns null when the config value is not set
     */
    public function test_configValueNotSet(): void
    {
        $burstApiConfig = new BurstApiConfig([]);

        self::assertNull($burstApiConfig->getBurstAddress());
    }
}
