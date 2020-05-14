<?php declare(strict_types=1);

namespace Burst\BurstPayment\Services;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class OrderTransactionService
{
    public const PAYMENT_CONTEXT_KEY = 'burst_payment_context';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    /**
     * @param OrderTransactionEntity $orderTransaction
     * @return array|mixed
     */
    public function getBurstPaymentContext(OrderTransactionEntity $orderTransaction)
    {
        return $orderTransaction->getCustomFields()[self::PAYMENT_CONTEXT_KEY] ?? [];
    }

    /**
     * @param OrderTransactionEntity $orderTransaction
     * @param Context $context
     * @param array $burstPaymentContext
     */
    public function setBurstPaymentContext(
        OrderTransactionEntity $orderTransaction,
        Context $context,
        array $burstPaymentContext
    ): void {
        $orderTransactionValues = [
            'id' => $orderTransaction->getId(),
            'customFields' => [
                self::PAYMENT_CONTEXT_KEY => $burstPaymentContext,
            ],
        ];
        $this->orderTransactionRepository->update([$orderTransactionValues], $context);
        $customFields = $orderTransaction->getCustomFields() ?? [];
        $customFields[self::PAYMENT_CONTEXT_KEY] = $burstPaymentContext;
        $orderTransaction->setCustomFields($customFields);
    }
}
