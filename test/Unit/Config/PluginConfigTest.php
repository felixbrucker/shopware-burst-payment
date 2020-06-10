<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\Config;

use Burst\BurstPayment\Config\PluginConfig;
use PHPUnit\Framework\TestCase;

/**
 * @testdox PluginConfig
 */
class PluginConfigTest extends TestCase
{
    /**
     * @testdox returns the config values when the config value is set
     */
    public function test_configValueSet(): void
    {
        $pluginConfig = new PluginConfig([
            'burstAddress' => 'someAddress',
            'burstWalletUrl' => 'someWalletUrl',
            'requiredConfirmationCount' => 10,
            'cancelUnmatchedOrdersAfterMinutes' => 300,
        ]);

        self::assertEquals('someAddress', $pluginConfig->getBurstAddress());
        self::assertEquals('someWalletUrl', $pluginConfig->getBurstWalletUrl());
        self::assertEquals(10, $pluginConfig->getRequiredConfirmationCount());
        self::assertEquals(300, $pluginConfig->getCancelUnmatchedOrdersAfterMinutes());
    }

    /**
     * @testdox returns null when the config value is not set
     */
    public function test_configValueNotSet(): void
    {
        $pluginConfig = new PluginConfig([]);

        self::assertNull($pluginConfig->getBurstAddress());
    }
}
