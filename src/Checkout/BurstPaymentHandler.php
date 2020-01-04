<?php declare(strict_types=1);

namespace Burst\BurstPayment\Checkout;

use Brick\Math\BigDecimal;
use Burst\BurstPayment\BurstApi\BurstAmount;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Services\PaymentContext;
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
    /**
     * @var PaymentContext
     */
    private $paymentContext;

    /**
     * @var BurstRateService
     */
    private $burstRateService;

    public function __construct(
        PaymentContext $paymentContext,
        BurstRateService $burstRateService
    ) {
        $this->paymentContext = $paymentContext;
        $this->burstRateService = $burstRateService;
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        $orderTransaction = $transaction->getOrderTransaction();

        try {
            $customer = $salesChannelContext->getCustomer();
            if (!$customer) {
                throw new CustomerNotLoggedInException();
            }

            $totalPrice = $orderTransaction->getAmount()->getTotalPrice();
            $currencyIsoCode = $salesChannelContext->getCurrency()->getIsoCode();
            $burstRate = $this->burstRateService->getBurstRate(strtolower($currencyIsoCode));
            if (!$burstRate) {
                throw new RuntimeException('Currency ' . $currencyIsoCode . ' can not be converted to BURST');
            }

            $totalPriceInBurstNQTToPay = $this->getUniqueBurstAmountInNQT($totalPrice, $burstRate->getRate());

            $burstPaymentContext = $this->paymentContext->getBurstPaymentContext($orderTransaction);
            $burstPaymentContext['amountToPayInNQT'] = $totalPriceInBurstNQTToPay;
            $burstPaymentContext['amountToPayInBurst'] = BurstAmount::fromNqtAmount($totalPriceInBurstNQTToPay)->toBurstAmount();
            $burstPaymentContext['burstRateUsed'] = $burstRate->getRate();
            $burstPaymentContext['transactionState'] = 'unmatched';
            $context = $salesChannelContext->getContext();
            $this->paymentContext->setBurstPaymentContext($orderTransaction, $context, $burstPaymentContext);
        } catch (Throwable $exception) {
            throw new SyncPaymentProcessException($orderTransaction->getId(), $exception->getMessage());
        }
    }

    /**
     * @param float $amount
     * @param float $rate
     * @return string
     */
    private function getUniqueBurstAmountInNQT(float $amount, float $rate): string
    {
        $totalPriceInBurstBN = BurstAmount::fromAmountWithRate($amount, $rate)->toBigDecimal();
        $uniqueSixDigitNumber = $this->getRandomSixDigitNumber();
        $totalPriceInBurstUniqueBN = $totalPriceInBurstBN->plus(BigDecimal::ofUnscaledValue($uniqueSixDigitNumber, 8));

        return BurstAmount::fromBurstAmount((string) $totalPriceInBurstUniqueBN)->toNQTAmount();
    }

    /**
     * @return string
     */
    private function getRandomSixDigitNumber(): string
    {
        return sprintf('%s%s%s%s%s%s', mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9));
    }
}
