<?php declare(strict_types=1);

namespace Burst\BurstPayment\Installation;

use Burst\BurstPayment\BurstPayment;
use Burst\BurstPayment\Payment\BurstPaymentHandler;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;

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

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    public function __construct(
        Context $context,
        PluginIdProvider $pluginIdProvider,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $mediaRepository,
        FileSaver $fileSaver
    ) {
        $this->context = $context;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->mediaRepository = $mediaRepository;
        $this->fileSaver = $fileSaver;
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
                'mediaId' => $this->ensureMedia(),
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

    private function ensureMedia(): string
    {
        $filePath = __DIR__ . '/../Resources/config/plugin.png';
        $fileName = hash_file('md5', $filePath);
        $media = $this->getMediaEntity($fileName);
        if ($media) {
            return $media->getId();
        }

        $mediaFile = new MediaFile(
            $filePath,
            mime_content_type($filePath),
            pathinfo($filePath, PATHINFO_EXTENSION),
            filesize($filePath)
        );
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create([
            [
                'id' => $mediaId,
            ],
        ], $this->context);

        $this->fileSaver->persistFileToMedia(
            $mediaFile,
            $fileName,
            $mediaId,
            $this->context
        );

        return $mediaId;
    }

    private function getMediaEntity(string $fileName): ?MediaEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));

        return $this->mediaRepository->search($criteria, $this->context)->first();
    }

    private function getPluginId(): string
    {
        return $this->pluginIdProvider->getPluginIdByBaseClass(
            BurstPayment::class,
            $this->context
        );
    }
}
