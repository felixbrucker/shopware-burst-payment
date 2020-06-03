<?php declare(strict_types=1);

namespace Burst\BurstPayment\Payment;

use Brick\Math\BigDecimal;
use Burst\BurstPayment\Currency\BurstAmount;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Services\OrderTransactionService;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class BurstPaymentHandler implements SynchronousPaymentHandlerInterface
{
    public const IDENTIFIER = 'burst_payment.payment_handler';

    /**
     * @var OrderTransactionService
     */
    private $orderTransactionService;

    /**
     * @var BurstRateService
     */
    private $burstRateService;

    public function __construct(
        OrderTransactionService $orderTransactionService,
        BurstRateService $burstRateService
    ) {
        $this->orderTransactionService = $orderTransactionService;
        $this->burstRateService = $burstRateService;
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        $customer = $salesChannelContext->getCustomer();
        if (!$customer) {
            throw new CustomerNotLoggedInException();
        }

        $orderTransaction = $transaction->getOrderTransaction();
        $totalPrice = $orderTransaction->getAmount()->getTotalPrice();
        $currencyIsoCode = $salesChannelContext->getCurrency()->getIsoCode(); // TODO: use currency of order
        $context = $salesChannelContext->getContext();

        try {
            $burstRate = $this->burstRateService->getBurstRate(
                mb_strtolower($currencyIsoCode),
                $context
            );
            if (!$burstRate) {
                throw new RuntimeException('Currency ' . $currencyIsoCode . ' can not be converted to BURST');
            }

            $totalPriceInBurstNQTToPay = $this->getUniqueBurstAmountInNQT($totalPrice, $burstRate->getRate());

            $burstPaymentContext = $this->orderTransactionService->getBurstPaymentContext($orderTransaction);
            $burstPaymentContext['amountToPayInNQT'] = $totalPriceInBurstNQTToPay;
            $burstPaymentContext['amountToPayInBurst'] = BurstAmount::fromNqtAmount($totalPriceInBurstNQTToPay)->toBurstAmount();
            $burstPaymentContext['burstRateUsed'] = $burstRate->getRate();
            $burstPaymentContext['transactionState'] = 'unmatched';
            $this->orderTransactionService->setBurstPaymentContext($orderTransaction, $context, $burstPaymentContext);
        } catch (Throwable $exception) {
            throw new SyncPaymentProcessException($orderTransaction->getId(), $exception->getMessage());
        }
    }

    private function getUniqueBurstAmountInNQT(float $amount, float $rate): string
    {
        $totalPriceInBurstBN = BurstAmount::fromAmountWithRate($amount, $rate)->toBigDecimal();
        if ($totalPriceInBurstBN->isZero()) {
            return '0';
        }
        $uniqueSixDigitNumber = $this->getRandomSixDigitNumber();
        $totalPriceInBurstUniqueBN = $totalPriceInBurstBN->plus(BigDecimal::ofUnscaledValue($uniqueSixDigitNumber, 8));

        return BurstAmount::fromBurstAmount((string) $totalPriceInBurstUniqueBN)->toNQTAmount();
    }

    private function getRandomSixDigitNumber(): string
    {
        return sprintf('%s%s%s%s%s%s', mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9));
    }
}
