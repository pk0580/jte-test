<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312102308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(12, 2) NOT NULL, weight NUMERIC(14, 3) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE order_stats (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(20) NOT NULL, group_by VARCHAR(10) NOT NULL, order_count INT NOT NULL, total_amount NUMERIC(15, 2) NOT NULL, INDEX idx_stats_period_group (period, group_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, number VARCHAR(20) DEFAULT NULL, status SMALLINT UNSIGNED DEFAULT 1 NOT NULL, name VARCHAR(255) NOT NULL, logistics_tracking_number VARCHAR(100) DEFAULT NULL, logistics_carrier_name VARCHAR(100) DEFAULT NULL, logistics_carrier_contact_data VARCHAR(255) DEFAULT NULL, logistics_weight_gross NUMERIC(12, 3) DEFAULT NULL, logistics_warehouse_data JSON DEFAULT NULL, logistics_address_equal TINYINT DEFAULT 1 NOT NULL, logistics_address_payer BIGINT UNSIGNED DEFAULT NULL, pricing_total_amount NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, pricing_total_weight NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, pricing_delivery_price_euro NUMERIC(12, 2) DEFAULT NULL, pricing_spec_price TINYINT DEFAULT NULL, metadata_hash VARCHAR(32) NOT NULL, metadata_token VARCHAR(64) NOT NULL, metadata_locale VARCHAR(5) NOT NULL, metadata_measure VARCHAR(10) DEFAULT \'unit\' NOT NULL, metadata_step SMALLINT UNSIGNED DEFAULT 1 NOT NULL, metadata_mirror SMALLINT UNSIGNED DEFAULT NULL, metadata_process TINYINT DEFAULT NULL, metadata_show_msg TINYINT DEFAULT NULL, metadata_description LONGTEXT DEFAULT NULL, metadata_bank_transfer_requested TINYINT DEFAULT NULL, metadata_accept_pay TINYINT DEFAULT NULL, customer_info_name VARCHAR(255) DEFAULT NULL, customer_info_surname VARCHAR(255) DEFAULT NULL, customer_info_email VARCHAR(150) DEFAULT NULL, customer_info_company_name VARCHAR(255) DEFAULT NULL, customer_info_sex SMALLINT UNSIGNED DEFAULT NULL, financial_terms_vat_type SMALLINT UNSIGNED DEFAULT 0 NOT NULL, financial_terms_vat_number VARCHAR(100) DEFAULT NULL, financial_terms_tax_number VARCHAR(50) DEFAULT NULL, financial_terms_discount NUMERIC(5, 2) DEFAULT NULL, financial_terms_cur_rate NUMERIC(14, 6) DEFAULT 1, financial_terms_currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, financial_terms_payment_euro TINYINT DEFAULT 0 NOT NULL, financial_terms_bank_details LONGTEXT DEFAULT NULL, delivery_terms_cost NUMERIC(12, 2) DEFAULT NULL, delivery_terms_type SMALLINT UNSIGNED DEFAULT 0, delivery_terms_time_min DATE DEFAULT NULL, delivery_terms_time_max DATE DEFAULT NULL, delivery_config_delivery_time_confirm_min DATE DEFAULT NULL, delivery_config_delivery_time_confirm_max DATE DEFAULT NULL, delivery_config_delivery_time_fast_pay_min DATE DEFAULT NULL, delivery_config_delivery_time_fast_pay_max DATE DEFAULT NULL, delivery_config_delivery_old_time_min DATE DEFAULT NULL, delivery_config_delivery_old_time_max DATE DEFAULT NULL, delivery_config_fact_date DATETIME DEFAULT NULL, delivery_config_sending_date DATETIME DEFAULT NULL, delivery_config_delivery_calculate_type SMALLINT UNSIGNED DEFAULT 0, delivery_config_weight_gross NUMERIC(12, 3) DEFAULT NULL, delivery_config_carrier_name VARCHAR(100) DEFAULT NULL, delivery_config_carrier_contact_data VARCHAR(255) DEFAULT NULL, delivery_address_index VARCHAR(20) DEFAULT NULL, delivery_address_country_id INT UNSIGNED DEFAULT NULL, delivery_address_region VARCHAR(100) DEFAULT NULL, delivery_address_city VARCHAR(200) DEFAULT NULL, delivery_address_address VARCHAR(300) DEFAULT NULL, delivery_address_building VARCHAR(200) DEFAULT NULL, delivery_address_apartment_office VARCHAR(30) DEFAULT NULL, delivery_address_phone_code VARCHAR(20) DEFAULT NULL, delivery_address_phone VARCHAR(30) DEFAULT NULL, dates_create_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, dates_update_at DATETIME DEFAULT NULL, dates_pay_date_execution DATETIME DEFAULT NULL, dates_offset_date DATETIME DEFAULT NULL, dates_proposed_date DATETIME DEFAULT NULL, dates_ship_date DATETIME DEFAULT NULL, dates_cancel_date DATETIME DEFAULT NULL, dates_full_payment_date DATETIME DEFAULT NULL, dates_offset_reason SMALLINT UNSIGNED DEFAULT NULL, manager_info_name VARCHAR(100) DEFAULT NULL, manager_info_email VARCHAR(150) DEFAULT NULL, manager_info_phone VARCHAR(30) DEFAULT NULL, review_product_review TINYINT DEFAULT NULL, review_entrance_review SMALLINT UNSIGNED DEFAULT NULL, pay_type_id INT NOT NULL, INDEX idx_orders_hash (metadata_hash), INDEX idx_orders_token (metadata_token), INDEX idx_orders_pay_type (pay_type_id), INDEX idx_orders_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE orders_article (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(14, 3) NOT NULL, price NUMERIC(12, 2) NOT NULL, price_eur NUMERIC(12, 2) DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, measure VARCHAR(10) DEFAULT NULL, delivery_time_min DATE DEFAULT NULL, delivery_time_max DATE DEFAULT NULL, weight NUMERIC(12, 3) NOT NULL, multiple_pallet SMALLINT UNSIGNED DEFAULT NULL, packaging_count NUMERIC(14, 3) NOT NULL, pallet NUMERIC(14, 3) NOT NULL, packaging NUMERIC(14, 3) NOT NULL, swimming_pool TINYINT DEFAULT 0 NOT NULL, orders_id INT DEFAULT NULL, article_id INT NOT NULL, INDEX idx_order_articles_order (orders_id), INDEX idx_order_articles_article (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE outbox_events (id INT AUTO_INCREMENT NOT NULL, event_type VARCHAR(255) NOT NULL, order_id INT NOT NULL, payload JSON NOT NULL, created_at DATETIME NOT NULL, scheduled_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, attempts INT NOT NULL, last_error LONGTEXT DEFAULT NULL, INDEX idx_outbox_process_lookup (processed_at, attempts), INDEX idx_outbox_scheduled_at (scheduled_at), INDEX idx_outbox_created_at (created_at), UNIQUE INDEX idx_outbox_unique_order_event (event_type, order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE pay_types (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE23A64B58 FOREIGN KEY (pay_type_id) REFERENCES pay_types (id)');
        $this->addSql('ALTER TABLE orders_article ADD CONSTRAINT FK_F34F7C1DCFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders_article ADD CONSTRAINT FK_F34F7C1D7294869C FOREIGN KEY (article_id) REFERENCES articles (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE23A64B58');
        $this->addSql('ALTER TABLE orders_article DROP FOREIGN KEY FK_F34F7C1DCFFE9AD6');
        $this->addSql('ALTER TABLE orders_article DROP FOREIGN KEY FK_F34F7C1D7294869C');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE order_stats');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE orders_article');
        $this->addSql('DROP TABLE outbox_events');
        $this->addSql('DROP TABLE pay_types');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
