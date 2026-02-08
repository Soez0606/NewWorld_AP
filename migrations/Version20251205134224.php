<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205134224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE archives (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, archive_date DATETIME NOT NULL, reason CLOB NOT NULL)');
        $this->addSql('CREATE TABLE contracts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, signature_date DATETIME NOT NULL, expiration_date DATETIME DEFAULT NULL, notice_months INTEGER NOT NULL, status VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, "action" CLOB NOT NULL, action_date DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE producers_info (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, contact_name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, address VARCHAR(255) NOT NULL, phone VARCHAR(50) NOT NULL, siret VARCHAR(14) NOT NULL, activity CLOB NOT NULL, registration_date DATETIME NOT NULL, validation_audit_date DATETIME DEFAULT NULL, termination_date DATETIME DEFAULT NULL, archived BOOLEAN DEFAULT NULL, status_audit VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL, role_id INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE archives');
        $this->addSql('DROP TABLE contracts');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE producers_info');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
