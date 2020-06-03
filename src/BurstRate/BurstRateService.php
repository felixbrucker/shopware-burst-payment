<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstRate;

use Burst\BurstPayment\Util\Util;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class BurstRateService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $burstRateRepository;

    /**
     * @var CoinGeckoApi
     */
    private $coinGeckoApi;

    public function __construct(
        EntityRepositoryInterface $burstRateRepository,
        CoinGeckoApi $coinGeckoApi
    ) {
        $this->burstRateRepository = $burstRateRepository;
        $this->coinGeckoApi = $coinGeckoApi;
    }

    public function getBurstRate(string $currency, Context $context): ?BurstRateEntity
    {
        return $this->burstRateRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('currency', $currency)),
            $context
        )->first();
    }

    public function updateRates(Context $context): void
    {
        $supportedCurrencies = $this->coinGeckoApi->getSupportedCurrencies();

        if (count($supportedCurrencies) === 0) {
            return;
        }
        $rates = $this->coinGeckoApi->getRates(['BURST'], $supportedCurrencies);

        /** @var BurstRateEntity[] $burstRates */
        $burstRates = $this->burstRateRepository->search(
            new Criteria(),
            $context
        )->getElements();

        $entitiesToUpsert = [];
        array_walk($rates['burst'], static function ($rate, $currency) use ($burstRates, &$entitiesToUpsert) {
            /** @var BurstRateEntity $existingBurstRate */
            $existingBurstRate = Util::arrayFind($burstRates, static function (BurstRateEntity $burstRate) use ($currency) {
                return $burstRate->getCurrency() === $currency;
            });
            if ($existingBurstRate && $existingBurstRate->getRate() !== $rate) {
                $entitiesToUpsert[] = [
                    'id' => $existingBurstRate->getId(),
                    'rate' => $rate,
                ];
            } elseif (!$existingBurstRate) {
                $entitiesToUpsert[] = [
                    'currency' => $currency,
                    'rate' => $rate,
                ];
            }
        });

        if (count($entitiesToUpsert) > 0) {
            $this->burstRateRepository->upsert(
                $entitiesToUpsert,
                $context
            );
        }

        $removedCurrencies = array_values(array_filter($burstRates, static function (BurstRateEntity $burstRate) use ($supportedCurrencies) {
            return !Util::arrayFind($supportedCurrencies, static function ($currency) use ($burstRate) {
                return $burstRate->getCurrency() === $currency;
            });
        }));

        if (count($removedCurrencies) > 0) {
            $this->burstRateRepository->delete(
                array_map(static function (BurstRateEntity $burstRate) {
                    return [
                        'id' => $burstRate->getId()
                    ];
                }, $removedCurrencies),
                $context
            );
        }
    }
}
