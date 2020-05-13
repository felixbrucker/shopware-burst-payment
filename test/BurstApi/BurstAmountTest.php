<?php

namespace Burst\BurstPayment\Test\BurstApi;

use Brick\Math\BigDecimal;
use Burst\BurstPayment\BurstApi\BurstAmount;
use PHPUnit\Framework\TestCase;

/**
 * @testdox BurstAmount
 */
class BurstAmountTest extends TestCase
{
    /**
     * @testdox returns the NQT amount when converting to NQT
     */
    public function test_toNQTAmount_withinBounds(): void
    {
        $burstAmount = BurstAmount::fromBurstAmount('1.2345');

        self::assertEquals('123450000', $burstAmount->toNQTAmount());
    }

    /**
     * @testdox returns the NQT amount scaled to 8 decimals when converting from Burst with more than 8 decimals
     */
    public function test_toNQTAmount_outOfBounds(): void
    {
        $burstAmount = BurstAmount::fromBurstAmount('123.23456789567');

        self::assertEquals('12323456790', $burstAmount->toNQTAmount());
    }

    /**
     * @testdox returns the Burst amount scaled to 8 decimals when converting to Burst
     */
    public function test_toBurstAmount_withinBounds(): void
    {
        $burstAmount = BurstAmount::fromBurstAmount('1.2345');

        self::assertEquals('1.23450000', $burstAmount->toBurstAmount());
    }

    /**
     * @testdox returns the Burst amount scaled to 8 decimals and rounded when converting to Burst with more than 8 decimals
     */
    public function test_toBurstAmount_outOfBounds(): void
    {
        $burstAmount = BurstAmount::fromBurstAmount('123.23456789567');

        self::assertEquals('123.23456790', $burstAmount->toBurstAmount());
    }

    /**
     * @testdox returns the Burst amount as BigDecimal when converting to BigDecimal
     */
    public function test_toBigDecimal(): void
    {
        $burstAmount = BurstAmount::fromBurstAmount('1.2345');

        self::assertEquals(BigDecimal::of('1.2345'), $burstAmount->toBigDecimal());
    }

    /**
     * @testdox scales the NQT amount to 8 decimals when converting from NQT
     */
    public function test_fromNqtAmount(): void
    {
        $burstAmount = BurstAmount::fromNqtAmount('12345');

        self::assertEquals(BigDecimal::of('0.00012345'), $burstAmount->toBigDecimal());
    }

    /**
     * @testdox divides the amount by the supplied rate and scales to 8 decimals when converting from amount with rate
     */
    public function test_fromAmountWithRate(): void
    {
        $burstAmount = BurstAmount::fromAmountWithRate(3.3, 3);

        self::assertEquals('1.10000000', (string)$burstAmount->toBigDecimal());
    }
}
