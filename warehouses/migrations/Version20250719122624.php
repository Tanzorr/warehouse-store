<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250719122624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_category_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_64C19C1796A8F92 (parent_category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_transaction (id INT AUTO_INCREMENT NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', warehouse_id INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, product_id INT NOT NULL, quantity INT NOT NULL, entity_type VARCHAR(255) NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, sku VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_reservation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, reserved_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', released_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', comment VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_reservation_item (id INT AUTO_INCREMENT NOT NULL, product_reservation_id INT NOT NULL, product_id INT NOT NULL, amount INT NOT NULL, INDEX IDX_87AE8C8AEB5BE9CB (product_reservation_id), INDEX IDX_87AE8C8A4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock_availability (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, warehouse_id INT NOT NULL, amount INT NOT NULL, INDEX IDX_1EDC98AC4584665A (product_id), INDEX IDX_1EDC98AC5080ECDE (warehouse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE warehouse (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(1000) NOT NULL, location VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1796A8F92 FOREIGN KEY (parent_category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product_reservation_item ADD CONSTRAINT FK_87AE8C8AEB5BE9CB FOREIGN KEY (product_reservation_id) REFERENCES product_reservation (id)');
        $this->addSql('ALTER TABLE product_reservation_item ADD CONSTRAINT FK_87AE8C8A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stock_availability ADD CONSTRAINT FK_1EDC98AC4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stock_availability ADD CONSTRAINT FK_1EDC98AC5080ECDE FOREIGN KEY (warehouse_id) REFERENCES warehouse (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1796A8F92');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product_reservation_item DROP FOREIGN KEY FK_87AE8C8AEB5BE9CB');
        $this->addSql('ALTER TABLE product_reservation_item DROP FOREIGN KEY FK_87AE8C8A4584665A');
        $this->addSql('ALTER TABLE stock_availability DROP FOREIGN KEY FK_1EDC98AC4584665A');
        $this->addSql('ALTER TABLE stock_availability DROP FOREIGN KEY FK_1EDC98AC5080ECDE');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE inventory_transaction');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_reservation');
        $this->addSql('DROP TABLE product_reservation_item');
        $this->addSql('DROP TABLE stock_availability');
        $this->addSql('DROP TABLE warehouse');
    }
}
