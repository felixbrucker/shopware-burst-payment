<?php declare(strict_types=1);

namespace Burst\BurstPayment;

use Burst\BurstPayment\Checkout\BurstPaymentHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

require_once __DIR__ . '/../autoload-dist/autoload.php';

class BurstPayment extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $context = $installContext->getContext();
        $pluginId = $this->container->get(PluginIdProvider::class)->getPluginIdByBaseClass(
            get_class($this),
            $context
        );
        // Check for existing 'Burst' payment method
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', BurstPaymentHandler::class));
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        $burstPaymentMethodId = $paymentMethodRepository->searchIds($criteria, $context)->firstId();
        $burstPaymentMethod = [
            'id' => $burstPaymentMethodId,
            'handlerIdentifier' => BurstPaymentHandler::class,
            'pluginId' => $pluginId,
            'translations' => [
                'de-DE' => [
                    'name' => 'Burst-Zahlung'
                ],
                'en-GB' => [
                    'name' => 'Burst payment',
                ],
            ],
        ];
        $paymentMethodRepository->upsert([
            $burstPaymentMethod,
        ], $context);

        parent::install($installContext);
    }
}
