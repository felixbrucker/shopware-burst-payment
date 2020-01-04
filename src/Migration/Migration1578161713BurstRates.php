<?php declare(strict_types=1);

namespace Burst\BurstPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1578161713BurstRates extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578161713;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `burst_rate` (
    `id` BINARY(16) NOT NULL,
    `currency` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `rate` DOUBLE,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
