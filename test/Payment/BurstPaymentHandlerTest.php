<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Payment;

use Burst\BurstPayment\BurstRate\BurstRateEntity;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Burst\BurstPayment\Services\OrderTransactionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @testdox BurstPaymentHandler
 */
class BurstPaymentHandlerTest extends TestCase
{
    /**
     * @var OrderTransactionService|MockObject
     */
    private $orderTransactionServiceMock;

    /**
     * @var BurstPaymentHandler
     */
    private $burstPaymentHandler;

    /**
     * @var OrderTransactionEntity
     */
    private $orderTransactionEntity;

    /**
     * @var SyncPaymentTransactionStruct
     */
    private $syncPaymentTransactionStruct;

    /**
     * @var RequestDataBag
     */
    private $requestDataBag;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContextMock;

    /**
     * @var CustomerEntity
     */
    private $customerEntity;

    /**
     * @var BurstRateEntity
     */
    private $burstRate;

    /**
     * @var CurrencyEntity
     */
    private $currency;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var array
     */
    private $paymentContext;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->orderTransactionServiceMock = $this->createMock(OrderTransactionService::class);
        $burstRateServiceMock = $this->createMock(BurstRateService::class);
        $this->burstPaymentHandler = new BurstPaymentHandler(
            $this->orderTransactionServiceMock,
            $burstRateServiceMock
        );
        $orderEntity = new OrderEntity();
        $orderEntity->setId('1234');
        $this->orderTransactionEntity = new OrderTransactionEntity();
        $this->orderTransactionEntity->setId('12345');
        $this->orderTransactionEntity->setOrder($orderEntity);
        $this->orderTransactionEntity->setAmount(new CalculatedPrice(
            100,
            100,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        $this->syncPaymentTransactionStruct = new SyncPaymentTransactionStruct(
            $this->orderTransactionEntity,
            $orderEntity
        );
        $this->requestDataBag = new RequestDataBag();
        $this->salesChannelContextMock = $this->createMock(SalesChannelContext::class);
        $this->customerEntity = new CustomerEntity();
        $this->salesChannelContextMock
            ->method('getCustomer')
            ->willReturnCallback(function () {
                return $this->customerEntity;
            });
        $this->currency = new CurrencyEntity();
        $this->currency->setDecimalPrecision(2);
        $this->currency->setIsoCode('EUR');
        $this->salesChannelContextMock
            ->method('getCurrency')
            ->willReturnCallback(function () {
                return $this->currency;
            });
        $this->context = Context::createDefaultContext();
        $this->salesChannelContextMock
            ->method('getContext')
            ->willReturn($this->context);
        $this->burstRate = new BurstRateEntity();
        $this->burstRate->setRate(2);
        $burstRateServiceMock->method('getBurstRate')->willReturnCallback(function () {
            return $this->burstRate;
        });
        $this->paymentContext = [];
        $this->orderTransactionServiceMock
            ->method('getBurstPaymentContext')
            ->willReturn($this->paymentContext);
    }

    /**
     * @testdox throws a CustomerNotLoggedInException exception when paying an order and the customer is not logged in
     */
    public function test_pay_customerNotLoggedIn(): void
    {
        $this->customerEntity = null;

        $this->expectExceptionObject(new CustomerNotLoggedInException());

        $this->burstPaymentHandler->pay(
            $this->syncPaymentTransactionStruct,
            $this->requestDataBag,
            $this->salesChannelContextMock
        );
    }

    /**
     * @testdox throws a SyncPaymentProcessException when paying an order and no burst rate is found for the sales channel currency
     */
    public function test_pay_noBurstRateFoundForCurrency(): void
    {
        $this->burstRate = null;
        $this->currency->setIsoCode('XYZ');

        $this->expectExceptionObject(new SyncPaymentProcessException(
            $this->orderTransactionEntity->getId(),
            'Currency XYZ can not be converted to BURST'
        ));

        $this->burstPaymentHandler->pay(
            $this->syncPaymentTransactionStruct,
            $this->requestDataBag,
            $this->salesChannelContextMock
        );
    }

    /**
     * @testdox uses 0 as amount to pay when paying an order and the total price is zero
     */
    public function test_pay_totalPriceIsZero(): void
    {
        $this->burstRate->setRate(3);
        $this->orderTransactionEntity->setAmount(new CalculatedPrice(
            0,
            0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransactionEntity,
                $this->context,
                [
                    'amountToPayInNQT' => '0',
                    'amountToPayInBurst' => '0.00000000',
                    'burstRateUsed' => 3,
                    'transactionState' => 'unmatched',
                ]
            );

        $this->burstPaymentHandler->pay(
            $this->syncPaymentTransactionStruct,
            $this->requestDataBag,
            $this->salesChannelContextMock
        );
    }

    /**
     * @testdox adds a very small random amount to the amount to pay to get a unique amount when paying an order
     */
    public function test_pay_addUniqueAmountToPrice(): void
    {
        $this->burstRate->setRate(1 / 3);
        $this->orderTransactionEntity->setAmount(new CalculatedPrice(
            2,
            2,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        $this->orderTransactionServiceMock
            ->method('setBurstPaymentContext')
            ->willReturnCallback(function ($orderTransaction, $context, $paymentContext) {
                $this->paymentContext = $paymentContext;
            });

        $this->burstPaymentHandler->pay(
            $this->syncPaymentTransactionStruct,
            $this->requestDataBag,
            $this->salesChannelContextMock
        );

        self::assertGreaterThan('6', $this->paymentContext['amountToPayInBurst']);
        self::assertGreaterThan('600000000', $this->paymentContext['amountToPayInNQT']);
    }
}
