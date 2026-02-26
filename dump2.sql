CREATE TABLE `orders` (
                          `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                          `hash` VARCHAR(32) NOT NULL,
                          `user_id` BIGINT UNSIGNED NULL,
                          `token` VARCHAR(64) NOT NULL,
                          `number` VARCHAR(20) NULL,

                          `status` SMALLINT UNSIGNED NOT NULL DEFAULT 1,

                          `email` VARCHAR(150) NULL,

                          `vat_type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                          `vat_number` VARCHAR(100) NULL,
                          `tax_number` VARCHAR(50) NULL,

                          `discount` DECIMAL(5,2) NULL,
                          `delivery` DECIMAL(12,2) NULL,

                          `delivery_type` TINYINT UNSIGNED DEFAULT 0 NULL,

                          `delivery_time_min` DATE NULL,
                          `delivery_time_max` DATE NULL,
                          `delivery_time_confirm_min` DATE NULL,
                          `delivery_time_confirm_max` DATE NULL,
                          `delivery_time_fast_pay_min` DATE NULL,
                          `delivery_time_fast_pay_max` DATE NULL,
                          `delivery_old_time_min` DATE NULL,
                          `delivery_old_time_max` DATE NULL,

                          `delivery_index` VARCHAR(20) NULL,
                          `delivery_country` INT UNSIGNED NULL,
                          `delivery_region` VARCHAR(100) NULL,
                          `delivery_city` VARCHAR(200) NULL,
                          `delivery_address` VARCHAR(300) NULL,
                          `delivery_building` VARCHAR(200) NULL,
                          `delivery_apartment_office` VARCHAR(30) NULL,
                          `delivery_phone_code` VARCHAR(20) NULL,
                          `delivery_phone` VARCHAR(30) NULL,

                          `sex` TINYINT UNSIGNED NULL,
                          `client_name` VARCHAR(255) NULL,
                          `client_surname` VARCHAR(255) NULL,
                          `company_name` VARCHAR(255) NULL,

                          `pay_type` SMALLINT UNSIGNED NOT NULL,
                          `pay_date_execution` DATETIME NULL,

                          `offset_date` DATETIME NULL,
                          `offset_reason` TINYINT UNSIGNED NULL,
                          `proposed_date` DATETIME NULL,
                          `ship_date` DATETIME NULL,

                          `tracking_number` VARCHAR(100) NULL,

                          `manager_name` VARCHAR(100) NULL,
                          `manager_email` VARCHAR(150) NULL,
                          `manager_phone` VARCHAR(30) NULL,

                          `carrier_name` VARCHAR(100) NULL,
                          `carrier_contact_data` VARCHAR(255) NULL,

                          `locale` VARCHAR(5) NOT NULL,
                          `cur_rate` DECIMAL(14,6) DEFAULT 1.000000 NULL,
                          `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
                          `measure` VARCHAR(10) NOT NULL DEFAULT 'm',

                          `name` VARCHAR(255) NOT NULL,
                          `description` TEXT NULL,

                          `create_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `update_date` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                          `warehouse_data` JSON NULL,

                          `step` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                          `address_equal` BOOLEAN DEFAULT TRUE,
                          `bank_transfer_requested` BOOLEAN DEFAULT NULL,
                          `accept_pay` BOOLEAN DEFAULT NULL,
                          `cancel_date` DATETIME NULL,

                          `weight_gross` DECIMAL(12,3) NULL,

                          `product_review` BOOLEAN NULL,
                          `mirror` SMALLINT UNSIGNED NULL,
                          `process` BOOLEAN NULL,
                          `fact_date` DATETIME NULL,
                          `entrance_review` SMALLINT UNSIGNED NULL,

                          `payment_euro` BOOLEAN DEFAULT FALSE,
                          `spec_price` BOOLEAN NULL,
                          `show_msg` BOOLEAN NULL,
                          `delivery_price_euro` DECIMAL(12,2) NULL,

                          `address_payer` BIGINT UNSIGNED NULL,
                          `sending_date` DATETIME NULL,
                          `delivery_calculate_type` TINYINT UNSIGNED DEFAULT 0 NULL,
                          `full_payment_date` DATE NULL,

                          `bank_details` TEXT NULL,

                          UNIQUE KEY `uk_orders_hash` (`hash`),
                          UNIQUE KEY `uk_orders_token` (`token`),

                          INDEX `idx_orders_user` (`user_id`),
                          INDEX `idx_orders_status` (`status`),
                          INDEX `idx_orders_create` (`create_date`),
                          INDEX `idx_orders_status_create` (`status`, `create_date`),
                          INDEX `idx_orders_country` (`delivery_country`),
                          INDEX `idx_orders_number` (`number`)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders_article` (
                                  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                  `orders_id` BIGINT UNSIGNED NOT NULL,
                                  `article_id` BIGINT UNSIGNED NULL,

                                  `amount` DECIMAL(14,3) NOT NULL,
                                  `price` DECIMAL(12,2) NOT NULL,
                                  `price_eur` DECIMAL(12,2) NULL,

                                  `currency` CHAR(3) NULL,
                                  `measure` VARCHAR(10) NULL,

                                  `delivery_time_min` DATE NULL,
                                  `delivery_time_max` DATE NULL,

                                  `weight` DECIMAL(12,3) NOT NULL,

                                  `multiple_pallet` TINYINT UNSIGNED NULL,
                                  `packaging_count` DECIMAL(14,3) NOT NULL,
                                  `pallet` DECIMAL(14,3) NOT NULL,
                                  `packaging` DECIMAL(14,3) NOT NULL,

                                  `swimming_pool` BOOLEAN NOT NULL DEFAULT FALSE,

                                  CONSTRAINT `fk_orders_article_order`
                                      FOREIGN KEY (`orders_id`) REFERENCES `orders` (`id`)
                                          ON DELETE CASCADE,

                                  INDEX `idx_orders_article_order` (`orders_id`),
                                  INDEX `idx_orders_article_article` (`article_id`)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
