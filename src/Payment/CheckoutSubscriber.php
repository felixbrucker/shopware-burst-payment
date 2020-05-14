<?php declare(strict_types=1);

namespace Burst\BurstPayment\Payment;

use Brick\Math\RoundingMode;
use Burst\BurstPayment\Config\PluginConfigService;
use Burst\BurstPayment\Currency\BurstAmount;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Services\OrderTransactionService;
use chillerlan\QRCode\QRCode;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var PluginConfigService
     */
    private $pluginConfigService;

    /**
     * @var BurstRateService
     */
    private $burstRateService;

    public function __construct(
        PluginConfigService $pluginConfigService,
        BurstRateService $burstRateService
    ) {
        $this->pluginConfigService = $pluginConfigService;
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
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== BurstPaymentHandler::IDENTIFIER) {
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

        $page->addExtension(BurstPageExtension::PAGE_EXTENSION_NAME, new BurstPageExtension([
            'estimatedAmountToPayInBurst' => $estimatedAmountToPayInBurst,
        ]));
    }

    public function onCheckoutFinishPageLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== BurstPaymentHandler::IDENTIFIER) {
            return;
        }
        $page = $event->getPage();
        $order = $page->getOrder();
        $orderTransaction = $order->getTransactions()->first();
        if (!$orderTransaction) {
            return;
        }
        $orderTransactionCustomFields = $orderTransaction->getCustomFields();
        if (!$orderTransactionCustomFields || !isset($orderTransactionCustomFields[OrderTransactionService::PAYMENT_CONTEXT_KEY]['amountToPayInNQT'])) {
            return;
        }

        $pluginConfig = $this->pluginConfigService->getPluginConfigForSalesChannel(
            $salesChannelContext->getSalesChannel()->getId()
        );
        $amountToPayInNqt = $orderTransactionCustomFields[OrderTransactionService::PAYMENT_CONTEXT_KEY]['amountToPayInNQT'];
        $fee = BurstAmount::fromBurstAmount('0.0294')->toNQTAmount(); // TODO: fetch from suggested fee?
        $requestBurstURI = vsprintf('burst://requestBurst?receiver=%s&amountNQT=%s&feeNQT=%s&immutable=false', [
            $pluginConfig->getBurstAddress(),
            $amountToPayInNqt,
            $fee,
        ]);
        $burstRateUsed = $orderTransactionCustomFields[OrderTransactionService::PAYMENT_CONTEXT_KEY]['burstRateUsed'];

        $page->addExtension(BurstPageExtension::PAGE_EXTENSION_NAME, new BurstPageExtension([
            'amountToPayInNQT' => $amountToPayInNqt,
            'amountToPayInBurst' => BurstAmount::fromNqtAmount($amountToPayInNqt)->toBurstAmount(),
            'burstRateUsed' => $burstRateUsed,
            'burstAddressToSendTo' => $pluginConfig->getBurstAddress(),
            'cancelUnmatchedOrdersAfterMinutes' => $pluginConfig->getCancelUnmatchedOrdersAfterMinutes(),
            'qrCodeDataUri' => (new QRCode())->render($requestBurstURI),
        ]));
    }
}
