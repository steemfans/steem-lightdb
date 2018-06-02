<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180602004854 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comments CHANGE json_metadata json_metadata LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE json_metadata json_metadata LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts CHANGE json_metadata json_metadata LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comments CHANGE json_metadata json_metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE posts CHANGE json_metadata json_metadata JSON NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE json_metadata json_metadata JSON NOT NULL');
    }
}
