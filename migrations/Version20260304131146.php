<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304131146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE orders ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, ADD measure VARCHAR(10) DEFAULT \'unit\' NOT NULL, CHANGE financial_terms_cur_rate financial_terms_cur_rate NUMERIC(14, 6) DEFAULT 1');
        $this->addSql('CREATE INDEX idx_orders_hash ON orders (hash)');
        $this->addSql('CREATE INDEX idx_orders_token ON orders (token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP INDEX idx_orders_hash ON orders');
        $this->addSql('DROP INDEX idx_orders_token ON orders');
        $this->addSql('ALTER TABLE orders DROP currency, DROP measure, CHANGE financial_terms_cur_rate financial_terms_cur_rate NUMERIC(14, 6) DEFAULT \'1.000000\'');
    }
}
