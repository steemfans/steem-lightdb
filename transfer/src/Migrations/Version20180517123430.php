<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180517123430 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_relations');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_relations (follower_id BIGINT UNSIGNED NOT NULL, following_id BIGINT UNSIGNED NOT NULL, INDEX IDX_148C329CAC24F853 (follower_id), INDEX IDX_148C329C1816E3A3 (following_id), PRIMARY KEY(follower_id, following_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_relations ADD CONSTRAINT FK_148C329C1816E3A3 FOREIGN KEY (following_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_relations ADD CONSTRAINT FK_148C329CAC24F853 FOREIGN KEY (follower_id) REFERENCES users (id)');
    }
}
