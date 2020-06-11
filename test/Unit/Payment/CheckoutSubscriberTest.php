<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\Payment;

use Burst\BurstPayment\BurstRate\BurstRateEntity;
use Burst\BurstPayment\BurstRate\BurstRateService;
use Burst\BurstPayment\Config\PluginConfig;
use Burst\BurstPayment\Config\PluginConfigService;
use Burst\BurstPayment\Payment\BurstPageExtension;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Burst\BurstPayment\Payment\CheckoutSubscriber;
use Burst\BurstPayment\Services\OrderTransactionService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @testdox CheckoutSubscriber
 */
class CheckoutSubscriberTest extends TestCase
{
    /**
     * @var CheckoutSubscriber
     */
    private $checkoutSubscriber;

    /**
     * @var OrderTransactionEntity
     */
    private $orderTransactionEntity;

    /**
     * @var BurstRateEntity
     */
    private $burstRate;

    /**
     * @var CurrencyEntity
     */
    private $currency;

    /**
     * @var PaymentMethodEntity
     */
    private $paymentMethodEntity;

    /**
     * @var CheckoutConfirmPage
     */
    private $checkoutConfirmPage;

    /**
     * @var CheckoutConfirmPageLoadedEvent
     */
    private $checkoutConfirmPageLoadedEvent;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var CheckoutFinishPage
     */
    private $checkoutFinishPage;

    /**
     * @var CheckoutFinishPageLoadedEvent
     */
    private $checkoutFinishPageLoadedEvent;

    /**
     * @var PluginConfig
     */
    private $pluginConfig;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $pluginConfigServiceMock = $this->createMock(PluginConfigService::class);
        $burstRateServiceMock = $this->createMock(BurstRateService::class);
        $this->checkoutSubscriber = new CheckoutSubscriber(
            $pluginConfigServiceMock,
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
        $this->orderTransactionEntity->setCustomFields([
            OrderTransactionService::PAYMENT_CONTEXT_KEY => [
                'amountToPayInNQT' => '1234567890',
                'burstRateUsed' => 1,
            ],
        ]);
        $orderEntity->setTransactions(new OrderTransactionCollection());
        $orderEntity->getTransactions()->add($this->orderTransactionEntity);
        $salesChannelContextMock = $this->createMock(SalesChannelContext::class);
        $this->paymentMethodEntity = new PaymentMethodEntity();
        $this->paymentMethodEntity->setHandlerIdentifier(BurstPaymentHandler::IDENTIFIER);
        $salesChannelContextMock
            ->method('getPaymentMethod')
            ->willReturnCallback(function () {
                return $this->paymentMethodEntity;
            });
        $this->currency = new CurrencyEntity();
        $this->currency->setDecimalPrecision(2);
        $this->currency->setIsoCode('EUR');
        $salesChannelContextMock
            ->method('getCurrency')
            ->willReturnCallback(function () {
                return $this->currency;
            });
        $context = Context::createDefaultContext();
        $salesChannelContextMock
            ->method('getContext')
            ->willReturn($context);
        $this->burstRate = new BurstRateEntity();
        $this->burstRate->setRate(2);
        $burstRateServiceMock->method('getBurstRate')->willReturnCallback(function () {
            return $this->burstRate;
        });
        $this->checkoutConfirmPage = new CheckoutConfirmPage(
            new PaymentMethodCollection(),
            new ShippingMethodCollection()
        );
        $this->cart = new Cart('test', 'token');
        $this->cart->setPrice(new CartPrice(
            100,
            100,
            100,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));
        $this->checkoutConfirmPage->setCart($this->cart);
        $this->checkoutConfirmPageLoadedEvent = new CheckoutConfirmPageLoadedEvent(
            $this->checkoutConfirmPage,
            $salesChannelContextMock,
            new Request()
        );
        $this->checkoutFinishPage = new CheckoutFinishPage();
        $this->checkoutFinishPageLoadedEvent = new CheckoutFinishPageLoadedEvent(
            $this->checkoutFinishPage,
            $salesChannelContextMock,
            new Request()
        );
        $this->checkoutFinishPage->setOrder($this->orderTransactionEntity->getOrder());
        $this->pluginConfig = new PluginConfig([
            'burstAddress' => 'my addr',
            'cancelUnmatchedOrdersAfterMinutes' => 10,
        ]);
        $pluginConfigServiceMock->method('getPluginConfigForSalesChannel')->willReturnCallback(function () {
            return $this->pluginConfig;
        });
    }

    /**
     * @testdox does nothing when the checkout confirm page is loaded and burst payment is not selected
     */
    public function test_onCheckoutConfirmPageLoaded_burstPaymentNotSelected(): void
    {
        $this->paymentMethodEntity->setHandlerIdentifier('something else');

        $this->checkoutSubscriber->onCheckoutConfirmPageLoaded($this->checkoutConfirmPageLoadedEvent);

        self::assertFalse($this->checkoutConfirmPage->hasExtension(BurstPageExtension::PAGE_EXTENSION_NAME));
    }

    /**
     * @testdox does nothing when the checkout confirm page is loaded and no burst rate is found
     */
    public function test_onCheckoutConfirmPageLoaded_noBurstRate(): void
    {
        $this->burstRate = null;

        $this->checkoutSubscriber->onCheckoutConfirmPageLoaded($this->checkoutConfirmPageLoadedEvent);

        self::assertFalse($this->checkoutConfirmPage->hasExtension(BurstPageExtension::PAGE_EXTENSION_NAME));
    }

    /**
     * @testdox adds the estimatedAmountToPayInBurst in the BurstPageExtension when the checkout confirm page is loaded and a burst rate is found for the currency
     */
    public function test_onCheckoutConfirmPageLoaded_burstRateFound(): void
    {
        $this->burstRate->setRate(3);
        $this->cart->setPrice(new CartPrice(
            12.99,
            12.99,
            12.99,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        $this->checkoutSubscriber->onCheckoutConfirmPageLoaded($this->checkoutConfirmPageLoadedEvent);

        self::assertSame(
            '4.33',
            $this->checkoutConfirmPage->getExtension(BurstPageExtension::PAGE_EXTENSION_NAME)->data['estimatedAmountToPayInBurst']
        );
    }

    /**
     * @testdox does nothing when the checkout finish page is loaded and burst payment is not selected
     */
    public function test_onCheckoutFinishPageLoaded_burstPaymentNotSelected(): void
    {
        $this->paymentMethodEntity->setHandlerIdentifier('something else');

        $this->checkoutSubscriber->onCheckoutFinishPageLoaded($this->checkoutFinishPageLoadedEvent);

        self::assertFalse($this->checkoutFinishPage->hasExtension(BurstPageExtension::PAGE_EXTENSION_NAME));
    }

    /**
     * @testdox does nothing when the checkout finish page is loaded and no order transaction exists for the given order
     */
    public function test_onCheckoutFinishPageLoaded_noOrderTransactionExists(): void
    {
        $this->orderTransactionEntity->getOrder()->setTransactions(new OrderTransactionCollection());

        $this->checkoutSubscriber->onCheckoutFinishPageLoaded($this->checkoutFinishPageLoadedEvent);

        self::assertFalse($this->checkoutFinishPage->hasExtension(BurstPageExtension::PAGE_EXTENSION_NAME));
    }

    /**
     * @testdox does nothing when the checkout finish page is loaded and amountToPayInNQT is not set on the order transaction
     */
    public function test_onCheckoutFinishPageLoaded_amountToPayInNQTNotSet(): void
    {
        $this->orderTransactionEntity->setCustomFields([
            OrderTransactionService::PAYMENT_CONTEXT_KEY => [],
        ]);

        $this->checkoutSubscriber->onCheckoutFinishPageLoaded($this->checkoutFinishPageLoadedEvent);

        self::assertFalse($this->checkoutFinishPage->hasExtension(BurstPageExtension::PAGE_EXTENSION_NAME));
    }

    /**
     * @testdox sets the BurstPageExtension when the checkout finish page is loaded and amountToPayInNQT is set on the order transaction
     */
    public function test_onCheckoutFinishPageLoaded_setBurstPageExtension(): void
    {
        $this->orderTransactionEntity->setCustomFields([
            OrderTransactionService::PAYMENT_CONTEXT_KEY => [
                'amountToPayInNQT' => '1299000000',
                'burstRateUsed' => 3,
            ],
        ]);
        $this->pluginConfig = new PluginConfig([
            'burstAddress' => 'my other addr',
            'cancelUnmatchedOrdersAfterMinutes' => 1337,
        ]);

        $this->checkoutSubscriber->onCheckoutFinishPageLoaded($this->checkoutFinishPageLoadedEvent);

        $burstPageExtensionData = $this->checkoutFinishPage->getExtension(BurstPageExtension::PAGE_EXTENSION_NAME)->data;
        self::assertSame('1299000000', $burstPageExtensionData['amountToPayInNQT']);
        self::assertSame('12.99000000', $burstPageExtensionData['amountToPayInBurst']);
        self::assertSame(3, $burstPageExtensionData['burstRateUsed']);
        self::assertSame('my other addr', $burstPageExtensionData['burstAddressToSendTo']);
        self::assertSame(1337, $burstPageExtensionData['cancelUnmatchedOrdersAfterMinutes']);
        self::assertArrayHasKey('qrCodeDataUri', $burstPageExtensionData);
    }
}
