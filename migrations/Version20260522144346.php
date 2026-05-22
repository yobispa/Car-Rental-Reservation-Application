<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522144346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronize Doctrine schema with the current entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY `FK_RESERVATIONS_CAR_ID`');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY `FK_RESERVATIONS_CUSTOMER_ID`');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY `FK_RESERVATIONS_USER_ID`');
        $this->addSql('ALTER TABLE reservations CHANGE payment_status payment_status VARCHAR(20) NOT NULL');
        $this->addSql('DROP INDEX idx_reservations_car_id ON reservations');
        $this->addSql('CREATE INDEX IDX_4DA239C3C6F69F ON reservations (car_id)');
        $this->addSql('DROP INDEX idx_reservations_customer_id ON reservations');
        $this->addSql('CREATE INDEX IDX_4DA2399395C3F3 ON reservations (customer_id)');
        $this->addSql('DROP INDEX idx_reservations_user_id ON reservations');
        $this->addSql('CREATE INDEX IDX_4DA239A76ED395 ON reservations (user_id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT `FK_RESERVATIONS_CAR_ID` FOREIGN KEY (car_id) REFERENCES cars (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT `FK_RESERVATIONS_CUSTOMER_ID` FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT `FK_RESERVATIONS_USER_ID` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA239C3C6F69F');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA2399395C3F3');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA239A76ED395');
        $this->addSql('ALTER TABLE reservations CHANGE payment_status payment_status VARCHAR(20) DEFAULT \'not_started\' NOT NULL');
        $this->addSql('DROP INDEX idx_4da239a76ed395 ON reservations');
        $this->addSql('CREATE INDEX IDX_RESERVATIONS_USER_ID ON reservations (user_id)');
        $this->addSql('DROP INDEX idx_4da239c3c6f69f ON reservations');
        $this->addSql('CREATE INDEX IDX_RESERVATIONS_CAR_ID ON reservations (car_id)');
        $this->addSql('DROP INDEX idx_4da2399395c3f3 ON reservations');
        $this->addSql('CREATE INDEX IDX_RESERVATIONS_CUSTOMER_ID ON reservations (customer_id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA239C3C6F69F FOREIGN KEY (car_id) REFERENCES cars (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA2399395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA239A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
