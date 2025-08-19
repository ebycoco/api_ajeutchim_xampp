<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250721175305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, participants_id JSON NOT NULL, recipient_id VARCHAR(255) DEFAULT NULL, name_participant VARCHAR(255) NOT NULL, name_recipient VARCHAR(255) NOT NULL, last_message LONGTEXT DEFAULT NULL, last_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', message_status VARCHAR(255) NOT NULL, unread_count INT DEFAULT NULL, online TINYINT(1) NOT NULL, new_conversation TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cotisation (id INT AUTO_INCREMENT NOT NULL, matricule_id INT NOT NULL, montant NUMERIC(10, 0) NOT NULL, annee VARCHAR(255) NOT NULL, cotised TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AE64D2ED9AAADC05 (matricule_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE matricule (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, montant_adhesion NUMERIC(10, 2) NOT NULL, annee_adhesion VARCHAR(255) NOT NULL, commune VARCHAR(255) DEFAULT NULL, quartier VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, avatar_path VARCHAR(255) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, envoyeur_id VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B6BD307F9AC0396 (conversation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, matricule_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, commune VARCHAR(255) DEFAULT NULL, quartier VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, avatar_path VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D6499AAADC05 (matricule_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cotisation ADD CONSTRAINT FK_AE64D2ED9AAADC05 FOREIGN KEY (matricule_id) REFERENCES matricule (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499AAADC05 FOREIGN KEY (matricule_id) REFERENCES matricule (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cotisation DROP FOREIGN KEY FK_AE64D2ED9AAADC05');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499AAADC05');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE cotisation');
        $this->addSql('DROP TABLE matricule');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
