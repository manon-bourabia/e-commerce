<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327150300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD phone VARCHAR(255) NOT NULL, ADD adresse VARCHAR(255) NOT NULL, ADD is_completed TINYINT DEFAULT NULL, ADD email VARCHAR(255) NOT NULL, ADD is_payment_completed TINYINT DEFAULT NULL, DROP phone_number, DROP adress, CHANGE create_at created_at DATETIME NOT NULL, CHANGE total total_price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993988BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_5242B8EBA35F2858 FOREIGN KEY (_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_5242B8EB4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(180) NOT NULL, CHANGE image image VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(180) NOT NULL, CHANGE last_name last_name VARCHAR(180) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993988BAC62AF');
        $this->addSql('ALTER TABLE `order` ADD phone_number VARCHAR(255) NOT NULL, ADD adress VARCHAR(255) NOT NULL, DROP phone, DROP adresse, DROP is_completed, DROP email, DROP is_payment_completed, CHANGE created_at create_at DATETIME NOT NULL, CHANGE total_price total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE order_products DROP FOREIGN KEY FK_5242B8EBA35F2858');
        $this->addSql('ALTER TABLE order_products DROP FOREIGN KEY FK_5242B8EB4584665A');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(255) NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL');
    }
}
