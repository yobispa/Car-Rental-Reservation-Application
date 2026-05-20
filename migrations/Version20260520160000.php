<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customers and reservations with car, customer, and optional user links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservations (id INT AUTO_INCREMENT NOT NULL, car_id INT NOT NULL, customer_id INT NOT NULL, user_id INT DEFAULT NULL, number_of_persons INT NOT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_RESERVATIONS_CAR_ID (car_id), INDEX IDX_RESERVATIONS_CUSTOMER_ID (customer_id), INDEX IDX_RESERVATIONS_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RESERVATIONS_CAR_ID FOREIGN KEY (car_id) REFERENCES cars (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RESERVATIONS_CUSTOMER_ID FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RESERVATIONS_USER_ID FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_RESERVATIONS_CAR_ID');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_RESERVATIONS_CUSTOMER_ID');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_RESERVATIONS_USER_ID');
        $this->addSql('DROP TABLE reservations');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE `user`');
    }
}
