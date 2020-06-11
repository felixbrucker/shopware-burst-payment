<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\Services;

use Burst\BurstPayment\Services\OrderTransactionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @testdox OrderTransactionService
 */
class OrderTransactionServiceTest extends TestCase
{
    /**
     * @var OrderTransactionService
     */
    private $orderTransactionService;
    /**
     * @var MockObject|EntityRepositoryInterface
     */
    private $orderTransactionRepositoryMock;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var OrderTransactionEntity
     */
    private $orderTransaction;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->orderTransactionRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->orderTransactionService = new OrderTransactionService(
            $this->orderTransactionRepositoryMock
        );
        $this->orderTransaction = new OrderTransactionEntity();
        $this->orderTransaction->setId('123');
        $this->context = Context::createDefaultContext();
    }

    /**
     * @testdox returns an empty array when retrieving the payment context for an order transaction
     */
    public function test_getBurstPaymentContext_noPaymentContextSet(): void
    {
        $this->orderTransaction->setCustomFields([]);

        $paymentContext = $this->orderTransactionService->getBurstPaymentContext($this->orderTransaction);

        self::assertSame([], $paymentContext);
    }

    /**
     * @testdox returns an empty array when retrieving the payment context for an order transaction
     */
    public function test_getBurstPaymentContext_paymentContextSet(): void
    {
        $this->orderTransaction->setCustomFields([
            OrderTransactionService::PAYMENT_CONTEXT_KEY => [
                'test' => '123',
            ],
        ]);

        $paymentContext = $this->orderTransactionService->getBurstPaymentContext($this->orderTransaction);

        self::assertSame([
            'test' => '123',
        ], $paymentContext);
    }

    /**
     * @testdox updates the db when setting the payment context for an order transaction
     */
    public function test_setBurstPaymentContext_paymentContextSet_updateDb(): void
    {
        $paymentContext = [
            'test123' => '999',
        ];

        $this->orderTransactionRepositoryMock
            ->expects(self::once())
            ->method('update')
            ->with(
                [
                    [
                        'id' => $this->orderTransaction->getId(),
                        'customFields' => [
                            OrderTransactionService::PAYMENT_CONTEXT_KEY => [
                                'test123' => '999',
                            ],
                        ],
                    ]
                ],
                $this->context
            );

        $this->orderTransactionService->setBurstPaymentContext(
            $this->orderTransaction,
            $this->context,
            $paymentContext
        );
    }

    /**
     * @testdox updates the entity when setting the payment context for an order transaction
     */
    public function test_setBurstPaymentContext_paymentContextSet_updateEntity(): void
    {
        $paymentContext = [
            'test123' => '999',
        ];

        $this->orderTransactionService->setBurstPaymentContext(
            $this->orderTransaction,
            $this->context,
            $paymentContext
        );

        self::assertSame([
            'test123' => '999',
        ], $this->orderTransaction->getCustomFields()[OrderTransactionService::PAYMENT_CONTEXT_KEY]);
    }
}
