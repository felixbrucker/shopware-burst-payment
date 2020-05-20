<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Payment;

use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Burst\BurstPayment\Services\OrderTransactionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
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
     * @var BurstRateService|MockObject
     */
    private $burstRateServiceMock;

    /**
     * @var BurstPaymentHandler
     */
    private $burstPaymentHandler;

    /**
     * @var OrderEntity
     */
    private $orderEntity;

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
     * @before
     */
    public function setUpTest(): void
    {
        $this->orderTransactionServiceMock = $this->createMock(OrderTransactionService::class);
        $this->burstRateServiceMock = $this->createMock(BurstRateService::class);
        $this->burstPaymentHandler = new BurstPaymentHandler(
            $this->orderTransactionServiceMock,
            $this->burstRateServiceMock
        );
        $this->orderEntity = new OrderEntity();
        $this->orderEntity->setId('1234');
        $this->orderTransactionEntity = new OrderTransactionEntity();
        $this->orderTransactionEntity->setId('12345');
        $this->orderTransactionEntity->setOrder($this->orderEntity);
        $this->syncPaymentTransactionStruct = new SyncPaymentTransactionStruct(
            $this->orderTransactionEntity,
            $this->orderEntity
        );
        $this->requestDataBag = new RequestDataBag();
        $this->salesChannelContextMock = $this->createMock(SalesChannelContext::class);
        $this->customerEntity = new CustomerEntity();
        $this->salesChannelContextMock
            ->method('getCustomer')
            ->willReturnCallback(function () {
                return $this->customerEntity;
            });
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
}
