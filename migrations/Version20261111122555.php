<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111122555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motorcycle ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE motorcycle ADD CONSTRAINT FK_21E380E1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_21E380E1B03A8386 ON motorcycle (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motorcycle DROP FOREIGN KEY FK_21E380E1B03A8386');
        $this->addSql('DROP INDEX IDX_21E380E1B03A8386 ON motorcycle');
        $this->addSql('ALTER TABLE motorcycle DROP created_by_id');
    }
}
