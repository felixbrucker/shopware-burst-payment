<?php

namespace Burst\BurstPayment\Test\Services;

use Burst\BurstPayment\BurstApi\BurstApi;
use Burst\BurstPayment\BurstApi\BurstApiException;
use Burst\BurstPayment\BurstApi\BurstApiFactory;
use Burst\BurstPayment\Config\PluginConfig;
use Burst\BurstPayment\Config\PluginConfigService;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Burst\BurstPayment\Services\OpenOrdersService;
use Burst\BurstPayment\Services\OrderTransactionService;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class OpenOrdersServiceTest extends TestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;
    /**
     * @var MockObject|EntityRepositoryInterface
     */
    private $orderTransactionRepositoryMock;
    /**
     * @var OrderTransactionService|MockObject
     */
    private $orderTransactionServiceMock;
    /**
     * @var BurstApiFactory|MockObject
     */
    private $burstApiFactoryMock;
    /**
     * @var MockObject|OrderTransactionStateHandler
     */
    private $orderTransactionStateHandlerMock;
    /**
     * @var OpenOrdersService
     */
    private $openOrdersService;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var array
     */
    private $orderTransactionSearchResult;
    /**
     * @var OrderTransactionEntity
     */
    private $orderTransaction;
    /**
     * @var BurstApi|MockObject
     */
    private $burstApiMock;
    /**
     * @var array
     */
    private $paymentContext;
    /**
     * @var array
     */
    private $unconfirmedTransactions;
    /**
     * @var array
     */
    private $transactionsFrom;
    /**
     * @var PluginConfig
     */
    private $pluginConfig;
    /**
     * @var array
     */
    private $transaction;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->orderTransactionRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->orderTransactionServiceMock = $this->createMock(OrderTransactionService::class);
        $this->burstApiFactoryMock = $this->createMock(BurstApiFactory::class);
        $pluginConfigServiceMock = $this->createMock(PluginConfigService::class);
        $this->orderTransactionStateHandlerMock = $this->createMock(OrderTransactionStateHandler::class);
        $this->openOrdersService = new OpenOrdersService(
            $this->loggerMock,
            $this->orderTransactionRepositoryMock,
            $this->orderTransactionServiceMock,
            $this->burstApiFactoryMock,
            $pluginConfigServiceMock,
            $this->orderTransactionStateHandlerMock
        );
        $this->context = Context::createDefaultContext();
        $this->orderTransaction = new OrderTransactionEntity();
        $this->orderTransaction->setId('1234');
        $order = new OrderEntity();
        $order->setOrderDateTime(new DateTime());
        $order->setOrderNumber('123');
        $this->orderTransaction->setOrder($order);
        $this->orderTransactionSearchResult = [
            $this->orderTransaction
        ];
        $this->orderTransactionRepositoryMock->method('search')->willReturnCallback(function () {
            $searchResultMock = $this->createMock(EntitySearchResult::class);
            $searchResultMock->method('getElements')->willReturn($this->orderTransactionSearchResult);

            return $searchResultMock;
        });
        $this->burstApiMock = $this->createMock(BurstApi::class);
        $this->burstApiFactoryMock->method('createBurstApiForSalesChannel')->willReturn($this->burstApiMock);
        $this->paymentContext = [
            'transactionId' => '123',
        ];
        $this->orderTransactionServiceMock->method('getBurstPaymentContext')->willReturnCallback(function () {
            return $this->paymentContext;
        });
        $this->unconfirmedTransactions = [];
        $this->burstApiMock->method('getUnconfirmedTransactions')->willReturnCallback(function () {
            return $this->unconfirmedTransactions;
        });
        $this->transactionsFrom = [];
        $this->burstApiMock->method('getTransactionsFrom')->willReturnCallback(function () {
            return $this->transactionsFrom;
        });
        $this->pluginConfig = new PluginConfig([]);
        $pluginConfigServiceMock->method('getPluginConfigForSalesChannel')->willReturnCallback(function () {
            return $this->pluginConfig;
        });
        $this->transaction = [];
        $this->burstApiMock->method('getTransaction')->willReturnCallback(function () {
            return $this->transaction;
        });
    }

    /**
     * @testdox fetches only open orders when matching unmatched orders
     */
    public function test_matchUnmatchedOrders_fetchOnlyOpenOrders(): void
    {
        $this->orderTransactionRepositoryMock
            ->expects(self::once())
            ->method('search')
            ->with(
                (new Criteria())->addFilter(
                    new EqualsFilter('order_transaction.stateMachineState.technicalName', OrderTransactionStates::STATE_OPEN),
                    new EqualsFilter('order_transaction.paymentMethod.handlerIdentifier', BurstPaymentHandler::IDENTIFIER)
                )->addAssociations([
                    'order',
                ])->addSorting(
                    new FieldSorting('order_transaction.order.orderDateTime', FieldSorting::ASCENDING)
                ),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox does nothing when matching unmatched orders and there are no open orders
     */
    public function test_matchUnmatchedOrders_noOpenOrders(): void
    {
        $this->orderTransactionSearchResult = [];

        $this->burstApiFactoryMock->expects(self::never())->method('createBurstApiForSalesChannel');

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox mark free orders as paid when matching unmatched orders
     */
    public function test_matchUnmatchedOrders_freeOrder(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '0',
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('paid')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox does nothing when matching unmatched orders and the amountToPayInNQT is not set
     */
    public function test_matchUnmatchedOrders_noAmountToPayInNQT(): void
    {
        $this->paymentContext = [];

        $this->orderTransactionStateHandlerMock
            ->expects(self::never())
            ->method('paid');
        $this->orderTransactionStateHandlerMock
            ->expects(self::never())
            ->method('process');

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox does nothing when matching unmatched orders and no match is found and the order creation happened within the allowed time frame of cancelUnmatchedOrdersAfterMinutes
     */
    public function test_matchUnmatchedOrders_noMatchingTransactionFound_orderDateWithinCancelUnmatchedOrdersAfterMinutes(): void
    {
        $this->orderTransaction->getOrder()->setOrderDateTime(new DateTime());
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::never())
            ->method('paid');
        $this->orderTransactionStateHandlerMock
            ->expects(self::never())
            ->method('process');

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox log a info message when matching unmatched orders and no match is found and the order creation happened outside of the allowed time frame of cancelUnmatchedOrdersAfterMinutes
     */
    public function test_matchUnmatchedOrders_noMatchingTransactionFound_orderDateExceedsCancelUnmatchedOrdersAfterMinutes_logInfo(): void
    {
        $this->pluginConfig = new PluginConfig([
            'cancelUnmatchedOrdersAfterMinutes' => 120,
        ]);
        $this->orderTransaction->getOrder()->setOrderDateTime((new DateTime())->sub(new \DateInterval('PT3H')));
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Canceling order because unmatched for greater than 120 minutes',
                [
                    'orderNumber' => $this->orderTransaction->getOrder()->getOrderNumber(),
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox updates the payment context when matching unmatched orders and no match is found and the order creation happened outside of the allowed time frame of cancelUnmatchedOrdersAfterMinutes
     */
    public function test_matchUnmatchedOrders_noMatchingTransactionFound_orderDateExceedsCancelUnmatchedOrdersAfterMinutes_paymentContextUpdated(): void
    {
        $this->pluginConfig = new PluginConfig([
            'cancelUnmatchedOrdersAfterMinutes' => 120,
        ]);
        $this->orderTransaction->getOrder()->setOrderDateTime((new DateTime())->sub(new \DateInterval('PT3H')));
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'amountToPayInNQT' => '1200000000',
                    'transactionState' => 'cancelled',
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox marks the order transaction as cancelled when matching unmatched orders and no match is found and the order creation happened outside of the allowed time frame of cancelUnmatchedOrdersAfterMinutes
     */
    public function test_matchUnmatchedOrders_noMatchingTransactionFound_orderDateExceedsCancelUnmatchedOrdersAfterMinutes_markOrderTransactionAsCancelled(): void
    {
        $this->pluginConfig = new PluginConfig([
            'cancelUnmatchedOrdersAfterMinutes' => 120,
        ]);
        $this->orderTransaction->getOrder()->setOrderDateTime((new DateTime())->sub(new \DateInterval('PT3H')));
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('cancel')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox logs a debug message when matching unmatched orders and a matching unconfirmed transaction is found
     */
    public function test_matchUnmatchedOrders_matchingUnconfirmedTransaction_logMatch(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->unconfirmedTransactions = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
            ],
        ];

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Matched order successfully',
                [
                    'orderNumber' => $this->orderTransaction->getOrder()->getOrderNumber(),
                    'burstTransactionId' => '1235',
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox persists the updated payment context when matching unmatched orders and a matching unconfirmed transaction is found
     */
    public function test_matchUnmatchedOrders_matchingUnconfirmedTransaction_persistUpdatedPaymentContext(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->unconfirmedTransactions = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
            ],
        ];

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'amountToPayInNQT' => '1200000000',
                    'transactionId' => '1235',
                    'senderAddress' => '0001',
                    'transactionState' => 'unconfirmed',
                    'confirmations' => 0,
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox persists the updated payment context when matching unmatched orders and a matching unconfirmed transaction is found
     */
    public function test_matchUnmatchedOrders_matchingUnconfirmedTransaction_markOrderAsProcessing(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->unconfirmedTransactions = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
            ],
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('process')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox logs a debug message when matching unmatched orders and a matching transaction is found
     */
    public function test_matchUnmatchedOrders_matchingTransaction_logMatch(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
            ],
        ];

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Matched order successfully',
                [
                    'orderNumber' => $this->orderTransaction->getOrder()->getOrderNumber(),
                    'burstTransactionId' => '1235',
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox persists the updated payment context when matching unmatched orders and a matching transaction is found and the matched transaction is not confirmed yet
     */
    public function test_matchUnmatchedOrders_matchingTransaction_pendingConfirmation_persistUpdatedPaymentContext_(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 7,
        ]);
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
                'confirmations' => 3,
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
                'confirmations' => 5,
            ],
        ];

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'amountToPayInNQT' => '1200000000',
                    'transactionId' => '1235',
                    'senderAddress' => '0001',
                    'transactionState' => 'pending',
                    'confirmations' => 5,
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox persists the updated payment context when matching unmatched orders and a matching transaction is found and the matched transaction is already confirmed
     */
    public function test_matchUnmatchedOrders_matchingTransaction_confirmed_persistUpdatedPaymentContext(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 7,
        ]);
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
                'confirmations' => 3,
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
                'confirmations' => 8,
            ],
        ];

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'amountToPayInNQT' => '1200000000',
                    'transactionId' => '1235',
                    'senderAddress' => '0001',
                    'transactionState' => 'confirmed',
                    'confirmations' => 8,
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox persists the updated payment context when matching unmatched orders and a matching transaction is found
     */
    public function test_matchUnmatchedOrders_matchingTransaction_markOrderAsProcessing(): void
    {
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
            ],
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('process')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox marks the order transaction as paid when matching unmatched orders and a matching transaction is found and the matched transaction is already confirmed
     */
    public function test_matchUnmatchedOrders_matchingTransaction_confirmed_markAsPaid(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 7,
        ]);
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
                'confirmations' => 3,
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
                'confirmations' => 8,
            ],
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('paid')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox logs a debug message when matching unmatched orders and a matching transaction is found and the matched transaction is already confirmed
     */
    public function test_matchUnmatchedOrders_matchingTransaction_confirmed_logDebugMessage(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 7,
        ]);
        $this->paymentContext = [
            'amountToPayInNQT' => '1200000000',
        ];
        $this->transactionsFrom = [
            [
                'amountNQT' => '1200000001',
                'transaction' => '1234',
                'senderRS' => '0000',
                'confirmations' => 3,
            ],
            [
                'amountNQT' => '1200000000',
                'transaction' => '1235',
                'senderRS' => '0001',
                'confirmations' => 8,
            ],
        ];

        $this->loggerMock
            ->expects(self::at(1))
            ->method('debug')
            ->with(
                'Marked order as paid after transaction matured',
                [
                    'orderNumber' => $this->orderTransaction->getOrder()->getOrderNumber(),
                    'burstTransactionId' => '1235',
                    'confirmations' => 8,
                ]
            );

        $this->openOrdersService->matchUnmatchedOrders($this->context);
    }

    /**
     * @testdox fetches only the processing orders when updating matched orders
     */
    public function test_updateMatchedOrders_fetchOnlyProcessingOrders(): void
    {
        $this->orderTransactionRepositoryMock
            ->expects(self::once())
            ->method('search')
            ->with(
                (new Criteria())->addFilter(
                    new EqualsFilter('order_transaction.stateMachineState.technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                    new EqualsFilter('order_transaction.paymentMethod.handlerIdentifier', BurstPaymentHandler::IDENTIFIER)
                )->addAssociations([
                    'order',
                ])->addSorting(
                    new FieldSorting('order_transaction.order.orderDateTime', FieldSorting::ASCENDING)
                ),
                $this->context
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox does nothing when updating matched orders and there are no processing orders
     */
    public function test_updateMatchedOrders_noProcessingOrders(): void
    {
        $this->orderTransactionSearchResult = [];

        $this->burstApiFactoryMock->expects(self::never())->method('createBurstApiForSalesChannel');

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox updates and persists the payment context when updating matched orders and the burst transaction is not found anymore
     */
    public function test_updateMatchedOrders_transactionNotFound_updatePaymentContext(): void
    {
        $this->burstApiMock->method('getTransaction')->willThrowException(new BurstApiException(
            [],
            'Unknown transaction',
            5
        ));

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'transactionState' => 'unmatched',
                ]
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox marks the order transaction as open again when updating matched orders and the burst transaction is not found anymore
     */
    public function test_updateMatchedOrders_transactionNotFound_reopenOrderTransaction(): void
    {
        $this->burstApiMock->method('getTransaction')->willThrowException(new BurstApiException(
            [],
            'Unknown transaction',
            5
        ));

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('reopen')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox does nothing when updating matched orders and the burst transaction is found and the confirmation count did not change
     */
    public function test_updateMatchedOrders_transactionFound_confirmationCountSame(): void
    {
        $this->paymentContext = [
            'transactionId' => '123',
            'confirmations' => 1,
        ];
        $this->transaction = [
            'confirmations' => 1,
        ];

        $this->orderTransactionServiceMock
            ->expects(self::never())
            ->method('setBurstPaymentContext');

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox updates and persists the payment context when updating matched orders and the burst transaction is found and the confirmation count did change
     */
    public function test_updateMatchedOrders_transactionFound_confirmationCountDiffers(): void
    {
        $this->paymentContext = [
            'transactionId' => '123',
            'confirmations' => 1,
        ];
        $this->transaction = [
            'confirmations' => 2,
        ];

        $this->orderTransactionServiceMock
            ->expects(self::once())
            ->method('setBurstPaymentContext')
            ->with(
                $this->orderTransaction,
                $this->context,
                [
                    'transactionId' => '123',
                    'confirmations' => 2,
                    'transactionState' => 'pending',
                ]
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox marks the order transaction as paid when updating matched orders and the burst transaction is found and the transaction is confirmed
     */
    public function test_updateMatchedOrders_transactionFound_confirmed_markOrderTransactionAsPaid(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 9,
        ]);
        $this->paymentContext = [
            'transactionId' => '123',
            'confirmations' => 1,
        ];
        $this->transaction = [
            'confirmations' => 22,
        ];

        $this->orderTransactionStateHandlerMock
            ->expects(self::once())
            ->method('paid')
            ->with(
                $this->orderTransaction->getId(),
                $this->context
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }

    /**
     * @testdox logs a debug message when updating matched orders and the burst transaction is found and the transaction is confirmed
     */
    public function test_updateMatchedOrders_transactionFound_confirmed_logDebugMessage(): void
    {
        $this->pluginConfig = new PluginConfig([
            'requiredConfirmationCount' => 9,
        ]);
        $this->paymentContext = [
            'transactionId' => '123',
            'confirmations' => 1,
        ];
        $this->transaction = [
            'confirmations' => 22,
        ];

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Marked order as paid after transaction matured',
                [
                    'orderNumber' => $this->orderTransaction->getOrder()->getOrderNumber(),
                    'burstTransactionId' => '123',
                    'confirmations' => 22,
                ]
            );

        $this->openOrdersService->updateMatchedOrders($this->context);
    }
}
