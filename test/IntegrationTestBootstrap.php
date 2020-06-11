<?php declare(strict_types=1);

use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

$shopwareDir = getenv('SHOPWARE_DIR');

(new Dotenv(true))->load($shopwareDir . '/.env');

putenv('DATABASE_URL=' . getenv('DATABASE_URL') . '_test');

$loader = require $shopwareDir . '/vendor/autoload.php';
KernelLifecycleManager::prepare($loader);
