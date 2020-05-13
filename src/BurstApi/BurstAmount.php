<?php

namespace Burst\BurstPayment\BurstApi;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class BurstAmount
{
    private const NQT_SCALE = 8;
    private const ROUNDING_SCALE = 8;

    /**
     * @var BigDecimal
     */
    private $amountInBurst;

    private function __construct(BigDecimal $amountInBurst)
    {
        $this->amountInBurst = $amountInBurst;
    }

    public function toNQTAmount(): string
    {
        return (string) $this->amountInBurst->toScale(self::ROUNDING_SCALE, RoundingMode::CEILING)->getUnscaledValue();
    }

    public function toBurstAmount(): string
    {
        return (string) $this->amountInBurst->toScale(self::ROUNDING_SCALE, RoundingMode::CEILING);
    }

    public function toBigDecimal(): BigDecimal
    {
        return $this->amountInBurst;
    }

    public static function fromNqtAmount(string $nqtAmount): BurstAmount
    {
        return new self(BigDecimal::ofUnscaledValue($nqtAmount, self::NQT_SCALE));
    }

    public static function fromBurstAmount(string $burstAmount): BurstAmount
    {
        return new self(BigDecimal::of($burstAmount));
    }

    public static function fromAmountWithRate(float $amount, float $rate): BurstAmount
    {
        return new self(BigDecimal::of($amount)->dividedBy($rate, self::ROUNDING_SCALE, RoundingMode::CEILING));
    }
}
