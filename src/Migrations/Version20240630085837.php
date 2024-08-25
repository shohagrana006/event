<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240630085837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to eventic_payment table';
    }

    public function up(Schema $schema): void
    {
        // Add the new status column
        $this->addSql('ALTER TABLE eventic_payment ADD status VARCHAR(255) NOT NULL AFTER order_id');
    }

    public function down(Schema $schema): void
    {
        // Remove the status column
        $this->addSql('ALTER TABLE eventic_payment DROP status');
    }
}
