<?php declare(strict_types=1);

namespace Burst\BurstPayment\Installation;

use Burst\BurstPayment\BurstPayment;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class BurstPaymentInstaller
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var PluginIdProvider
     */
    private $pluginIdProvider;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    public function __construct(
        Context $context,
        PluginIdProvider $pluginIdProvider,
        EntityRepositoryInterface $paymentMethodRepository
    ) {
        $this->context = $context;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function postInstall(): void
    {
        $this->postUpdate();
    }

    public function postUpdate(): void
    {
        $this->ensurePaymentMethod();
    }

    private function ensurePaymentMethod(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', BurstPaymentHandler::IDENTIFIER));
        $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $this->context)->firstId();

        $this->paymentMethodRepository->upsert([
            [
                'id' => $paymentMethodId,
                'handlerIdentifier' => BurstPaymentHandler::IDENTIFIER,
                'pluginId' => $this->getPluginId(),
                'translations' => [
                    'de-DE' => [
                        'name' => 'Burst-Zahlung'
                    ],
                    'en-GB' => [
                        'name' => 'Burst payment',
                    ],
                ],
            ],
        ], $this->context);
    }

    private function getPluginId(): string
    {
        return $this->pluginIdProvider->getPluginIdByBaseClass(
            BurstPayment::class,
            $this->context
        );
    }
}
