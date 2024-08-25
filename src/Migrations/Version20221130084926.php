<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221130084926 extends AbstractMigration {

    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE eventic_payment_gateway ADD instructions LONGTEXT DEFAULT NULL, CHANGE config config JSON NOT NULL COMMENT \'(DC2Type:json_array)\'');
    }

    public function down(Schema $schema): void {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE eventic_payment CHANGE details details LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE eventic_payment_gateway DROP instructions, CHANGE config config LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE eventic_payout_request CHANGE payment payment LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci');
    }

}
