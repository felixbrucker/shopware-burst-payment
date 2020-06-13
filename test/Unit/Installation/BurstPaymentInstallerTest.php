<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Unit\Installation;

use Burst\BurstPayment\Installation\BurstPaymentInstaller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

/**
 * @testdox BurstPaymentInstaller
 */
class BurstPaymentInstallerTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var MockObject|EntityRepositoryInterface
     */
    private $paymentMethodRepositoryMock;

    /**
     * @var MockObject|EntityRepositoryInterface
     */
    private $mediaRepositoryMock;

    /**
     * @var MockObject|FileSaver
     */
    private $fileSaverMock;

    /**
     * @var BurstPaymentInstaller
     */
    private $burstPaymentInstaller;

    /**
     * @var string
     */
    private $pluginId;

    /**
     * @var MediaEntity
     */
    private $mediaEntity;

    /**
     * @var |null
     */
    private $paymentMethodId;

    /**
     * @before
     */
    public function setUpTest(): void
    {
        $this->context = Context::createDefaultContext();
        $pluginIdProviderMock = $this->createMock(PluginIdProvider::class);
        $this->paymentMethodRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->mediaRepositoryMock = $this->createMock(EntityRepositoryInterface::class);
        $this->fileSaverMock = $this->createMock(FileSaver::class);
        $this->burstPaymentInstaller = new BurstPaymentInstaller(
            $this->context,
            $pluginIdProviderMock,
            $this->paymentMethodRepositoryMock,
            $this->mediaRepositoryMock,
            $this->fileSaverMock
        );
        $this->pluginId = '123';
        $pluginIdProviderMock->method('getPluginIdByBaseClass')->willReturnCallback(function () {
            return $this->pluginId;
        });
        $this->mediaEntity = new MediaEntity();
        $this->mediaEntity->setId('1234');
        $mediaSearchResultMock = $this->createMock(EntitySearchResult::class);
        $this->mediaRepositoryMock->method('search')->willReturn($mediaSearchResultMock);
        $mediaSearchResultMock->method('first')->willReturnCallback(function () {
            return $this->mediaEntity;
        });
        $paymentMethodSearchResultMock = $this->createMock(IdSearchResult::class);
        $this->paymentMethodRepositoryMock->method('searchIds')->willReturn($paymentMethodSearchResultMock);
        $this->paymentMethodId = null;
        $paymentMethodSearchResultMock->method('firstId')->willReturnCallback(function () {
            return $this->paymentMethodId;
        });
    }

    public function methodToExecuteProvider(): array
    {
        return [
            ['postInstall'],
            ['postUpdate'],
        ];
    }

    /**
     * @testdox creates a new payment method when running post install/update steps and no payment method exists yet
     *
     * @dataProvider methodToExecuteProvider
     * @param string $methodToExecute
     */
    public function test_postUpdate_paymentMethodDoesNotExistYet(string $methodToExecute): void
    {
        $this->pluginId = '1234';
        $this->mediaEntity->setId('12345');
        $this->paymentMethodId = null;

        $this->paymentMethodRepositoryMock
            ->expects(self::once())
            ->method('upsert')
            ->with([
                [
                    'id' => null,
                    'handlerIdentifier' => 'burst_payment.payment_handler',
                    'pluginId' => '1234',
                    'mediaId' => '12345',
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

        $this->burstPaymentInstaller->$methodToExecute();
    }

    /**
     * @testdox updates the payment method when running post install/update steps and the payment method exists already
     *
     * @dataProvider methodToExecuteProvider
     * @param string $methodToExecute
     */
    public function test_postUpdate_paymentMethodExists(string $methodToExecute): void
    {
        $this->pluginId = '1234';
        $this->mediaEntity->setId('12345');
        $this->paymentMethodId = '123456';

        $this->paymentMethodRepositoryMock
            ->expects(self::once())
            ->method('searchIds')
            ->with(
                (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', 'burst_payment.payment_handler')),
                $this->context
            );
        $this->paymentMethodRepositoryMock
            ->expects(self::once())
            ->method('upsert')
            ->with([
                [
                    'id' => '123456',
                    'handlerIdentifier' => 'burst_payment.payment_handler',
                    'pluginId' => '1234',
                    'mediaId' => '12345',
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

        $this->burstPaymentInstaller->$methodToExecute();
    }

    /**
     * @testdox searches the media entity via the md5 hash of the file when running post install/update steps
     *
     * @dataProvider methodToExecuteProvider
     * @param string $methodToExecute
     */
    public function test_postUpdate_searchMediaFileByHash(string $methodToExecute): void
    {
        $fileName = hash_file('md5', realpath(__DIR__ . '/../../../src/Resources/config/plugin.png'));

        $this->mediaRepositoryMock
            ->expects(self::once())
            ->method('search')
            ->with(
                (new Criteria())->addFilter(new EqualsFilter('fileName', $fileName)),
                $this->context
            );

        $this->burstPaymentInstaller->$methodToExecute();
    }

    /**
     * @testdox creates a media entity and persists the file linked with it when running post install/update steps and no media entity exists yet
     *
     * @dataProvider methodToExecuteProvider
     * @param string $methodToExecute
     */
    public function test_postUpdate_mediaEntityDoesNotExistYet(string $methodToExecute): void
    {
        $this->mediaEntity = null;
        $filePath = realpath(__DIR__ . '/../../../src/Resources/config/plugin.png');

        $this->mediaRepositoryMock
            ->expects(self::once())
            ->method('create')
            ->with($this->isType('array'), $this->context);
        $this->fileSaverMock
            ->expects(self::once())
            ->method('persistFileToMedia')
            ->with(
                new MediaFile(
                    $filePath,
                    mime_content_type($filePath),
                    pathinfo($filePath, PATHINFO_EXTENSION),
                    filesize($filePath)
                ),
                hash_file('md5', $filePath),
                $this->isType('string'),
                $this->context
            );

        $this->burstPaymentInstaller->$methodToExecute();
    }

    /**
     * @testdox activates the payment method when running activation steps and the payment method exists
     */
    public function test_activate_paymentMethodExists(): void
    {
        $this->paymentMethodId = '12345';

        $this->paymentMethodRepositoryMock
            ->expects(self::once())
            ->method('update')
            ->with([
                 [
                     'id' => '12345',
                     'active' => true,
                 ],
            ], $this->context);

        $this->burstPaymentInstaller->activate();
    }

    /**
     * @testdox does not activate the payment method when running activation steps and the payment method does not exist
     */
    public function test_activate_paymentMethodDoesNotExist(): void
    {
        $this->paymentMethodId = null;

        $this->paymentMethodRepositoryMock
            ->expects(self::never())
            ->method('update');

        $this->burstPaymentInstaller->activate();
    }

    /**
     * @testdox deactivates the payment method when running deactivation steps and the payment method exists
     */
    public function test_deactivate_paymentMethodExists(): void
    {
        $this->paymentMethodId = '12345';

        $this->paymentMethodRepositoryMock
            ->expects(self::once())
            ->method('update')
            ->with([
                [
                    'id' => '12345',
                    'active' => false,
                ],
            ], $this->context);

        $this->burstPaymentInstaller->deactivate();
    }

    /**
     * @testdox does not deactivate the payment method when running deactivation steps and the payment method does not exist
     */
    public function test_deactivate_paymentMethodDoesNotExist(): void
    {
        $this->paymentMethodId = null;

        $this->paymentMethodRepositoryMock
            ->expects(self::never())
            ->method('update');

        $this->burstPaymentInstaller->deactivate();
    }
}
