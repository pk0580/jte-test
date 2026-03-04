<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304225000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Optimize database indexes for orders, order_articles and outbox_events';
    }

    public function up(Schema $schema): void
    {
        // Outbox events optimization
        $this->addSql('DROP INDEX idx_outbox_processed_at ON outbox_events');
        $this->addSql('CREATE INDEX idx_outbox_process_lookup ON outbox_events (processed_at, attempts)');
        $this->addSql('CREATE INDEX idx_outbox_created_at ON outbox_events (created_at)');

        // Orders optimization
        $this->addSql('CREATE INDEX idx_orders_pay_type ON orders (pay_type_id)');
        $this->addSql('CREATE INDEX idx_orders_status ON orders (status)');

        // Order articles optimization
        $this->addSql('CREATE INDEX idx_order_articles_order ON orders_article (orders_id)');
        $this->addSql('CREATE INDEX idx_order_articles_article ON orders_article (article_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_outbox_process_lookup ON outbox_events');
        $this->addSql('DROP INDEX idx_outbox_created_at ON outbox_events');
        $this->addSql('CREATE INDEX idx_outbox_processed_at ON outbox_events (processed_at)');

        $this->addSql('DROP INDEX idx_orders_pay_type ON orders');
        $this->addSql('DROP INDEX idx_orders_status ON orders');

        $this->addSql('DROP INDEX idx_order_articles_order ON orders_article');
        $this->addSql('DROP INDEX idx_order_articles_article ON orders_article');
    }
}
