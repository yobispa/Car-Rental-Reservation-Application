<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cars table and seed the starter rental fleet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cars (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, category VARCHAR(30) NOT NULL, make VARCHAR(60) NOT NULL, model VARCHAR(80) NOT NULL, model_year INT NOT NULL, seats INT NOT NULL, doors INT NOT NULL, transmission VARCHAR(20) NOT NULL, fuel_type VARCHAR(20) NOT NULL, luggage_capacity INT NOT NULL, color VARCHAR(60) NOT NULL, daily_base_price NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, image_filename VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_CARS_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO cars (code, category, make, model, model_year, seats, doors, transmission, fuel_type, luggage_capacity, color, daily_base_price, status, image_filename) VALUES
            ('MIN-001', 'MINIVAN', 'Toyota', 'Sienna', 2022, 7, 5, 'AUTOMATIC', 'HYBRID', 5, 'Navy Blue', 95.00, 'AVAILABLE', 'minivan_001.png'),
            ('MIN-002', 'MINIVAN', 'Chrysler', 'Pacifica', 2021, 7, 5, 'AUTOMATIC', 'GASOLINE', 5, 'Silver Gray', 89.00, 'AVAILABLE', 'minivan_002.png'),
            ('MIN-003', 'MINIVAN', 'Honda', 'Odyssey', 2023, 7, 5, 'AUTOMATIC', 'GASOLINE', 5, 'Charcoal Gray', 98.00, 'AVAILABLE', 'minivan_003.png'),
            ('SED-001', 'SEDAN', 'Toyota', 'Camry', 2022, 5, 4, 'AUTOMATIC', 'GASOLINE', 3, 'Blue', 62.00, 'AVAILABLE', 'sedan_001.png'),
            ('SED-002', 'SEDAN', 'Hyundai', 'Sonata', 2021, 5, 4, 'AUTOMATIC', 'GASOLINE', 3, 'Dark Blue', 55.00, 'AVAILABLE', 'sedan_002.png'),
            ('SED-003', 'SEDAN', 'Honda', 'Accord', 2023, 5, 4, 'AUTOMATIC', 'HYBRID', 3, 'Gray', 68.00, 'AVAILABLE', 'sedan_003.png'),
            ('CON-001', 'CONVERTIBLE', 'Mazda', 'MX-5 Miata', 2022, 2, 2, 'AUTOMATIC', 'GASOLINE', 1, 'Blue', 78.00, 'AVAILABLE', 'convertible_001.png'),
            ('CON-002', 'CONVERTIBLE', 'BMW', 'Z4', 2021, 2, 2, 'AUTOMATIC', 'GASOLINE', 1, 'Metallic Gray', 115.00, 'AVAILABLE', 'convertible_002.png'),
            ('CON-003', 'CONVERTIBLE', 'Fiat', '124 Spider', 2020, 2, 2, 'AUTOMATIC', 'GASOLINE', 1, 'Green', 72.00, 'AVAILABLE', 'convertible_003.png')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cars');
    }
}
