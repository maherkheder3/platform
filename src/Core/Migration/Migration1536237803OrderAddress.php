<?php declare(strict_types=1);

namespace Shopware\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536237803OrderAddress extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536237803;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery('
            CREATE TABLE `order_address` (
              `id` binary(16) NOT NULL,
              `version_id` binary(16) NOT NULL,
              `country_id` binary(16) NOT NULL,
              `country_state_id` binary(16) NULL,
              `order_id` binary(16) NOT NULL,
              `order_version_id` binary(16) NOT NULL,
              `company` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
              `department` varchar(35) COLLATE utf8mb4_unicode_ci NULL,
              `salutation` varchar(30) COLLATE utf8mb4_unicode_ci NULL,
              `title` varchar(100) COLLATE utf8mb4_unicode_ci NULL,
              `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
              `last_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
              `street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `zipcode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
              `city` varchar(70) COLLATE utf8mb4_unicode_ci NOT NULL,
              `vat_id` varchar(50) COLLATE utf8mb4_unicode_ci NULL,
              `phone_number` varchar(40) COLLATE utf8mb4_unicode_ci NULL,
              `additional_address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
              `additional_address_line2` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3),
              PRIMARY KEY (`id`, `version_id`),
              CONSTRAINT `fk.order_address.country_id` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `fk.order_address.country_state_id` FOREIGN KEY (`country_state_id`) REFERENCES `country_state` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `fk.order_address.order_id` FOREIGN KEY (`order_id`, `order_version_id`) REFERENCES `order` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}