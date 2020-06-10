<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\BurstRate;

use Burst\BurstPayment\BurstRate\BurstRateEntity;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\BurstRate\CoinGeckoApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @testdox BurstRateService
 */
class BurstRateServiceTest extends TestCase
{
    /**
     * @var MockObject|EntityRepositoryInterface
     */
    private $burstRateRepositoryMock;

    /**
     * @var CoinGeckoApi|MockObject
     */
    private $coinGeckoApiMock;

    /**
     * @var BurstRateService
     */
    private $burstRateService;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MockObject|EntitySearchResult
     */
    private $entitySearchResultMock;

    /**
     * @var string[]
     */
    private $supportedCurrencies;

    /**
     * @var array
     */
    private $persistedBurstRateEntities;

    /**
     * @var \float[][]
     */
    private $burstRatesFromCoinGecko;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->burstRateRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->coinGeckoApiMock = $this->createMock(CoinGeckoApi::class);
        $this->burstRateService = new BurstRateService(
            $this->burstRateRepositoryMock,
            $this->coinGeckoApiMock
        );
        $this->context = Context::createDefaultContext();
        $this->entitySearchResultMock = $this->createMock(EntitySearchResult::class);
        $this->burstRateRepositoryMock->method('search')->willReturn($this->entitySearchResultMock);
        $this->entitySearchResultMock->method('first')->willReturn(new BurstRateEntity());
        $this->supportedCurrencies = ['eur', 'usd'];
        $this->coinGeckoApiMock->method('getSupportedCurrencies')->willReturnCallback(function () {
            return $this->supportedCurrencies;
        });
        $this->burstRatesFromCoinGecko = [
            'burst' => [
                'eur' => 0.00418151,
                'usd' => 0.00459392,
            ],
        ];
        $this->coinGeckoApiMock->method('getRates')->willReturnCallback(function () {
            return $this->burstRatesFromCoinGecko;
        });
        $this->persistedBurstRateEntities = [
            new BurstRateEntity(),
            new BurstRateEntity(),
        ];
        $this->persistedBurstRateEntities[0]->setCurrency('eur');
        $this->persistedBurstRateEntities[0]->setRate(0.1);
        $this->persistedBurstRateEntities[0]->setId('1');
        $this->persistedBurstRateEntities[1]->setCurrency('usd');
        $this->persistedBurstRateEntities[1]->setRate(0.2);
        $this->persistedBurstRateEntities[1]->setId('2');
        $this->entitySearchResultMock->method('getElements')->willReturnCallback(function () {
            return $this->persistedBurstRateEntities;
        });
    }

    /**
     * @testdox searches by the supplied currency when retrieving the burst rate
     */
    public function test_getBurstRate_searchByCurrency(): void
    {
        $this->burstRateRepositoryMock
            ->expects(self::once())
            ->method('search')
            ->with((new Criteria())->addFilter(new EqualsFilter('currency', 'eur')), $this->context);

        $this->burstRateService->getBurstRate('eur', $this->context);
    }

    /**
     * @testdox retrieves the first matching BurstRate for the supplied currency when retrieving the burst rate
     */
    public function test_getBurstRate_returnFirstMatch(): void
    {
        $this->entitySearchResultMock->expects(self::once())->method('first');

        $this->burstRateService->getBurstRate('eur', $this->context);
    }

    /**
     * @testdox does nothing when updating rates and no coins are supported on coin gecko
     */
    public function test_updateRates_noSupportedCurrencies(): void
    {
        $this->supportedCurrencies = [];

        $this->coinGeckoApiMock->expects(self::never())->method('getRates');

        $this->burstRateService->updateRates($this->context);
    }

    /**
     * @testdox fetches fresh rates from coin gecko when updating rates
     */
    public function test_updateRates_fetchFreshRates(): void
    {
        $this->supportedCurrencies = ['eur', 'usd'];

        $this->coinGeckoApiMock
            ->expects(self::once())
            ->method('getRates')
            ->with(['BURST'], ['eur', 'usd']);

        $this->burstRateService->updateRates($this->context);
    }

    /**
     * @testdox updates the currently persisted rates when updating rates
     */
    public function test_updateRates_updatePersistedRates(): void
    {
        $this->supportedCurrencies = ['eur', 'usd'];
        $this->burstRatesFromCoinGecko = [
            'burst' => [
                'eur' => 0.00418151,
                'usd' => 0.00459392,
            ],
        ];
        $this->persistedBurstRateEntities[0]->setCurrency('eur');
        $this->persistedBurstRateEntities[0]->setRate(0.2);
        $this->persistedBurstRateEntities[0]->setId('1');
        $this->persistedBurstRateEntities[1]->setCurrency('usd');
        $this->persistedBurstRateEntities[1]->setRate(0.3);
        $this->persistedBurstRateEntities[1]->setId('2');

        $this->burstRateRepositoryMock
            ->expects(self::once())
            ->method('upsert')
            ->with([
                [
                    'id' => '1',
                    'rate' => 0.00418151,
                ],
                [
                    'id' => '2',
                    'rate' => 0.00459392,
                ],
            ]);

        $this->burstRateService->updateRates($this->context);
    }

    /**
     * @testdox creates missing burst rates when updating rates
     */
    public function test_updateRates_createMissingPersistedRates(): void
    {
        $this->supportedCurrencies = ['eur', 'usd', 'gbp'];
        $this->burstRatesFromCoinGecko = [
            'burst' => [
                'eur' => 0.00418151,
                'usd' => 0.00459392,
                'gbp' => 0.00372038,
            ],
        ];
        $this->persistedBurstRateEntities[0]->setCurrency('eur');
        $this->persistedBurstRateEntities[0]->setRate(0.2);
        $this->persistedBurstRateEntities[0]->setId('1');
        $this->persistedBurstRateEntities[1]->setCurrency('usd');
        $this->persistedBurstRateEntities[1]->setRate(0.3);
        $this->persistedBurstRateEntities[1]->setId('2');

        $this->burstRateRepositoryMock
            ->expects(self::once())
            ->method('upsert')
            ->with([
                [
                    'id' => '1',
                    'rate' => 0.00418151,
                ],
                [
                    'id' => '2',
                    'rate' => 0.00459392,
                ],
                [
                    'currency' => 'gbp',
                    'rate' => 0.00372038,
                ],
            ]);

        $this->burstRateService->updateRates($this->context);
    }

    /**
     * @testdox deletes any persisted burst rates not supported by coin gecko anymore when updating rates
     */
    public function test_updateRates_deleteRemovedPersistedRates(): void
    {
        $this->supportedCurrencies = ['eur'];
        $this->persistedBurstRateEntities[0]->setCurrency('eur');
        $this->persistedBurstRateEntities[0]->setRate(0.2);
        $this->persistedBurstRateEntities[0]->setId('1');
        $this->persistedBurstRateEntities[1]->setCurrency('usd');
        $this->persistedBurstRateEntities[1]->setRate(0.3);
        $this->persistedBurstRateEntities[1]->setId('2');

        $this->burstRateRepositoryMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                [
                    'id' => '2',
                ],
            ], $this->context);

        $this->burstRateService->updateRates($this->context);
    }
}
