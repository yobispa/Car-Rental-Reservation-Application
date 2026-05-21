<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Sentoo payment fields to reservations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE reservations ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'not_started', ADD sentoo_transaction_id VARCHAR(100) DEFAULT NULL, ADD sentoo_payment_url VARCHAR(255) DEFAULT NULL, ADD sentoo_qr_code_url VARCHAR(255) DEFAULT NULL, ADD payment_message LONGTEXT DEFAULT NULL, ADD total_price NUMERIC(10, 2) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations DROP payment_status, DROP sentoo_transaction_id, DROP sentoo_payment_url, DROP sentoo_qr_code_url, DROP payment_message, DROP total_price');
    }
}
