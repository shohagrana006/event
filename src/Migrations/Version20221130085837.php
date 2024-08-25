<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221130085837 extends AbstractMigration {

    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE eventic_payment_gateway_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_F2ADA7DD2C2AC5D3 (translatable_id), UNIQUE INDEX eventic_payment_gateway_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE eventic_payment_gateway_translation ADD CONSTRAINT FK_F2ADA7DD2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES eventic_payment_gateway (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eventic_payment_gateway DROP instructions');
    }

    public function down(Schema $schema): void {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE eventic_payment_gateway_translation');
        $this->addSql('ALTER TABLE eventic_payment CHANGE details details LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE eventic_payment_gateway ADD instructions LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE eventic_payout_request CHANGE payment payment LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci');
    }

}
