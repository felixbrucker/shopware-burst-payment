<?php declare(strict_types=1);

namespace Burst\BurstPayment;

use Burst\BurstPayment\Installation\BurstPaymentInstaller;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

if (file_exists(__DIR__ . '/../autoload-dist/vendor/autoload.php')) {
    // The file does not exist if the plugin was installed via composer require of the Shopware project
    require_once(__DIR__ . '/../autoload-dist/vendor/autoload.php');
}

class BurstPayment extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__));
        $loader->load('BurstApi/DependencyInjection/service.xml');
        $loader->load('BurstRate/DependencyInjection/service.xml');
        $loader->load('Config/DependencyInjection/service.xml');
        $loader->load('Logging/DependencyInjection/service.xml');
        $loader->load('Payment/DependencyInjection/service.xml');
        $loader->load('Resources/config/snippets.xml');
        $loader->load('ScheduledTasks/DependencyInjection/service.xml');
        $loader->load('Services/DependencyInjection/service.xml');
    }

    public function postInstall(InstallContext $installContext): void
    {
        $installer = new BurstPaymentInstaller(
            $installContext->getContext(),
            $this->container->get(PluginIdProvider::class),
            $this->container->get('payment_method.repository'),
            $this->container->get('media.repository'),
            $this->container->get(FileSaver::class)
        );
        $installer->postInstall();

        parent::postInstall($installContext);
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        $installer = new BurstPaymentInstaller(
            $updateContext->getContext(),
            $this->container->get(PluginIdProvider::class),
            $this->container->get('payment_method.repository'),
            $this->container->get('media.repository'),
            $this->container->get(FileSaver::class)
        );
        $installer->postUpdate();

        parent::postUpdate($updateContext);
    }
}
