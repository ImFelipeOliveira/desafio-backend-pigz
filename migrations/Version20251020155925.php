<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020155925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fipe_entries (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', fipe_code VARCHAR(50) NOT NULL, brand VARCHAR(50) NOT NULL, model VARCHAR(50) NOT NULL, version VARCHAR(50) DEFAULT NULL, category VARCHAR(30) NOT NULL, fuel_type VARCHAR(20) NOT NULL, price_value NUMERIC(12, 2) NOT NULL, price_currency VARCHAR(3) NOT NULL, reference_month VARCHAR(7) NOT NULL, model_year INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE fipe_entries');
    }
}
