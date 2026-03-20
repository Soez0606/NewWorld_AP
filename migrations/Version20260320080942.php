<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320080942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contracts ADD COLUMN activity_description CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__contracts AS SELECT id, user_id, signature_date, expiration_date, notice_months, status FROM contracts');
        $this->addSql('DROP TABLE contracts');
        $this->addSql('CREATE TABLE contracts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, signature_date DATETIME NOT NULL, expiration_date DATETIME DEFAULT NULL, notice_months INTEGER NOT NULL, status VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO contracts (id, user_id, signature_date, expiration_date, notice_months, status) SELECT id, user_id, signature_date, expiration_date, notice_months, status FROM __temp__contracts');
        $this->addSql('DROP TABLE __temp__contracts');
    }
}
