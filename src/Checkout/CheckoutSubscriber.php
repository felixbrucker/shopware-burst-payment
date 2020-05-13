<?php

namespace Burst\BurstPayment\Checkout;

use Brick\Math\RoundingMode;
use Burst\BurstPayment\BurstApi\BurstAmount;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Services\SettingsService;
use chillerlan\QRCode\QRCode;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var BurstRateService
     */
    private $burstRateService;

    public function __construct(
        SettingsService $settingsService,
        BurstRateService $burstRateService
    ) {
        $this->settingsService = $settingsService;
        $this->burstRateService = $burstRateService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishPageLoaded',
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== BurstPaymentHandler::class) {
            return;
        }
        $page = $event->getPage();
        $totalPrice = $page->getCart()->getPrice()->getTotalPrice();
        $currencyIsoCode = $salesChannelContext->getCurrency()->getIsoCode();
        $burstRate = $this->burstRateService->getBurstRate(
            strtolower($currencyIsoCode),
            $event->getContext()
        );
        if (!$burstRate) {
            return;
        }

        $estimatedAmountToPayInBurst = (string) BurstAmount::fromAmountWithRate($totalPrice, $burstRate->getRate())
            ->toBigDecimal()
            ->toScale(2, RoundingMode::CEILING);

        $page->addExtension('burstData', new BurstData([
            'estimatedAmountToPayInBurst' => $estimatedAmountToPayInBurst,
        ]));
    }

    public function onCheckoutFinishPageLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== BurstPaymentHandler::class) {
            return;
        }
        $page = $event->getPage();
        $order = $page->getOrder();
        $orderTransaction = $order->getTransactions()->first();
        if (!$orderTransaction) {
            return;
        }
        $orderTransactionCustomFields = $orderTransaction->getCustomFields();
        if (!$orderTransactionCustomFields || !isset($orderTransactionCustomFields['burst_payment_context']['amountToPayInNQT'])) {
            return;
        }

        $burstAddressToSendTo = $this->settingsService->getConfigValue('burstAddress', $salesChannelContext->getSalesChannel()->getId());
        $amountToPayInNqt = $orderTransactionCustomFields['burst_payment_context']['amountToPayInNQT'];
        $fee = BurstAmount::fromBurstAmount('0.0294')->toNQTAmount();
        $requestBurstURI = vsprintf('burst://requestBurst?receiver=%s&amountNQT=%s&feeNQT=%s&immutable=false', [
            $burstAddressToSendTo,
            $amountToPayInNqt,
            $fee,
        ]);

        $cancelUnmatchedOrdersAfterMinutes = $this->settingsService->getConfigValue(
            'cancelUnmatchedOrdersAfterMinutes',
            $salesChannelContext->getSalesChannel()->getId()
        );

        $burstRateUsed = $orderTransactionCustomFields['burst_payment_context']['burstRateUsed'];
        $page->addExtension('burstData', new BurstData([
            'amountToPayInNQT' => $amountToPayInNqt,
            'amountToPayInBurst' => BurstAmount::fromNqtAmount($amountToPayInNqt)->toBurstAmount(),
            'burstRateUsed' => $burstRateUsed,
            'burstAddressToSendTo' => $burstAddressToSendTo,
            'cancelUnmatchedOrdersAfterMinutes' => $cancelUnmatchedOrdersAfterMinutes,
            'qrCodeDataUri' => (new QRCode())->render($requestBurstURI),
        ]));
    }
}
