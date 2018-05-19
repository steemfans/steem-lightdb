<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180519071501 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_relations ADD created_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comments ADD created_at INT DEFAULT NULL, ADD updated_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comments_votes ADD created_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD created_at INT DEFAULT NULL, ADD updated_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts_votes ADD created_at INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD created_at INT DEFAULT NULL, ADD updated_at INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comments DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE comments_votes DROP created_at');
        $this->addSql('ALTER TABLE posts DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE posts_votes DROP created_at');
        $this->addSql('ALTER TABLE user_relations DROP created_at');
        $this->addSql('ALTER TABLE users DROP created_at, DROP updated_at');
    }
}
