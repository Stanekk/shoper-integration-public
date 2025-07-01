<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604184342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE importer_file_data ADD number_of_products_excluded_by_status INT DEFAULT NULL, ADD number_of_products_stoc_not_changed INT DEFAULT NULL, CHANGE number_of_products_skipped number_of_not_found_products INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE importer_file_data ADD number_of_products_skipped INT DEFAULT NULL, DROP number_of_not_found_products, DROP number_of_products_excluded_by_status, DROP number_of_products_stoc_not_changed');
    }
}
