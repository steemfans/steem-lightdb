<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180619144613 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A4B89032C');
        $this->addSql('ALTER TABLE posts_tags DROP FOREIGN KEY FK_D5ECAD9FD5E258C5');
        $this->addSql('ALTER TABLE posts_votes DROP FOREIGN KEY FK_E38C21DE4B89032C');
        $this->addSql('CREATE TABLE comments_tags (comments_id BIGINT UNSIGNED NOT NULL, tags_id INT UNSIGNED NOT NULL, INDEX IDX_EB6DB98163379586 (comments_id), INDEX IDX_EB6DB9818D7B4FB4 (tags_id), PRIMARY KEY(comments_id, tags_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comments_tags ADD CONSTRAINT FK_EB6DB98163379586 FOREIGN KEY (comments_id) REFERENCES comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments_tags ADD CONSTRAINT FK_EB6DB9818D7B4FB4 FOREIGN KEY (tags_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE posts_tags');
        $this->addSql('DROP TABLE posts_votes');
        $this->addSql('DROP INDEX IDX_5F9E962A4B89032C ON comments');
        $this->addSql('ALTER TABLE comments ADD parent_author_text VARCHAR(500) NOT NULL, ADD author_text VARCHAR(500) NOT NULL, DROP post_id, CHANGE parent_author_id parent_author_id BIGINT UNSIGNED DEFAULT NULL, CHANGE author_id author_id BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('CREATE INDEX author_text_idx ON comments (author_text)');
        $this->addSql('CREATE INDEX parent_author_text_idx ON comments (parent_author_text)');
        $this->addSql('CREATE INDEX permlink_idx ON comments (permlink)');
        $this->addSql('CREATE INDEX parent_permlink_idx ON comments (parent_permlink)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE posts (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, main_tag_id INT UNSIGNED NOT NULL, author_id BIGINT UNSIGNED NOT NULL, permlink VARCHAR(500) NOT NULL COLLATE utf8mb4_unicode_ci, title VARCHAR(500) DEFAULT NULL COLLATE utf8mb4_unicode_ci, body LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, json_metadata LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, created_at INT DEFAULT NULL, updated_at INT DEFAULT NULL, is_del TINYINT(1) NOT NULL, INDEX IDX_885DBAFA25CEDB07 (main_tag_id), INDEX IDX_885DBAFAF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_tags (posts_id BIGINT UNSIGNED NOT NULL, tags_id INT UNSIGNED NOT NULL, INDEX IDX_D5ECAD9FD5E258C5 (posts_id), INDEX IDX_D5ECAD9F8D7B4FB4 (tags_id), PRIMARY KEY(posts_id, tags_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_votes (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, post_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, weight SMALLINT UNSIGNED NOT NULL, updown TINYINT(1) NOT NULL, created_at INT NOT NULL, updated_at INT NOT NULL, INDEX IDX_E38C21DE4B89032C (post_id), INDEX IDX_E38C21DEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA25CEDB07 FOREIGN KEY (main_tag_id) REFERENCES tags (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAF675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE posts_tags ADD CONSTRAINT FK_D5ECAD9F8D7B4FB4 FOREIGN KEY (tags_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_tags ADD CONSTRAINT FK_D5ECAD9FD5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_votes ADD CONSTRAINT FK_E38C21DE4B89032C FOREIGN KEY (post_id) REFERENCES posts (id)');
        $this->addSql('ALTER TABLE posts_votes ADD CONSTRAINT FK_E38C21DEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('DROP TABLE comments_tags');
        $this->addSql('DROP INDEX author_text_idx ON comments');
        $this->addSql('DROP INDEX parent_author_text_idx ON comments');
        $this->addSql('DROP INDEX permlink_idx ON comments');
        $this->addSql('DROP INDEX parent_permlink_idx ON comments');
        $this->addSql('ALTER TABLE comments ADD post_id BIGINT UNSIGNED NOT NULL, DROP parent_author_text, DROP author_text, CHANGE parent_author_id parent_author_id BIGINT UNSIGNED NOT NULL, CHANGE author_id author_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A4B89032C FOREIGN KEY (post_id) REFERENCES posts (id)');
        $this->addSql('CREATE INDEX IDX_5F9E962A4B89032C ON comments (post_id)');
    }
}
