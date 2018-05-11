<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180511172153 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE logs (id BIGINT AUTO_INCREMENT NOT NULL, which_table VARCHAR(255) NOT NULL, which_id BIGINT NOT NULL, block_num BIGINT NOT NULL, transaction_id VARCHAR(40) NOT NULL, operation_index INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comments (id BIGINT AUTO_INCREMENT NOT NULL, parent_id BIGINT DEFAULT NULL, permlink LONGTEXT NOT NULL, title LONGTEXT DEFAULT NULL, body LONGTEXT NOT NULL, json_metadata JSON DEFAULT NULL, INDEX IDX_5F9E962A727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id BIGINT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, json_metadata JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_relations (follower_id BIGINT NOT NULL, following_id BIGINT NOT NULL, INDEX IDX_148C329CAC24F853 (follower_id), INDEX IDX_148C329C1816E3A3 (following_id), PRIMARY KEY(follower_id, following_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tags (id INT AUTO_INCREMENT NOT NULL, tag_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_votes (id INT AUTO_INCREMENT NOT NULL, post_id BIGINT NOT NULL, user_id BIGINT NOT NULL, weight INT NOT NULL, updown TINYINT(1) NOT NULL, INDEX IDX_E38C21DE4B89032C (post_id), INDEX IDX_E38C21DEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts (id BIGINT AUTO_INCREMENT NOT NULL, main_tag_id INT NOT NULL, author_id BIGINT NOT NULL, permlink LONGTEXT NOT NULL, title LONGTEXT DEFAULT NULL, body LONGTEXT NOT NULL, json_metadata JSON NOT NULL, INDEX IDX_885DBAFA25CEDB07 (main_tag_id), INDEX IDX_885DBAFAF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_tags (posts_id BIGINT NOT NULL, tags_id INT NOT NULL, INDEX IDX_D5ECAD9FD5E258C5 (posts_id), INDEX IDX_D5ECAD9F8D7B4FB4 (tags_id), PRIMARY KEY(posts_id, tags_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A727ACA70 FOREIGN KEY (parent_id) REFERENCES comments (id)');
        $this->addSql('ALTER TABLE user_relations ADD CONSTRAINT FK_148C329CAC24F853 FOREIGN KEY (follower_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_relations ADD CONSTRAINT FK_148C329C1816E3A3 FOREIGN KEY (following_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE posts_votes ADD CONSTRAINT FK_E38C21DE4B89032C FOREIGN KEY (post_id) REFERENCES posts (id)');
        $this->addSql('ALTER TABLE posts_votes ADD CONSTRAINT FK_E38C21DEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA25CEDB07 FOREIGN KEY (main_tag_id) REFERENCES tags (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAF675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE posts_tags ADD CONSTRAINT FK_D5ECAD9FD5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_tags ADD CONSTRAINT FK_D5ECAD9F8D7B4FB4 FOREIGN KEY (tags_id) REFERENCES tags (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A727ACA70');
        $this->addSql('ALTER TABLE user_relations DROP FOREIGN KEY FK_148C329CAC24F853');
        $this->addSql('ALTER TABLE user_relations DROP FOREIGN KEY FK_148C329C1816E3A3');
        $this->addSql('ALTER TABLE posts_votes DROP FOREIGN KEY FK_E38C21DEA76ED395');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFAF675F31B');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA25CEDB07');
        $this->addSql('ALTER TABLE posts_tags DROP FOREIGN KEY FK_D5ECAD9F8D7B4FB4');
        $this->addSql('ALTER TABLE posts_votes DROP FOREIGN KEY FK_E38C21DE4B89032C');
        $this->addSql('ALTER TABLE posts_tags DROP FOREIGN KEY FK_D5ECAD9FD5E258C5');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_relations');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE posts_votes');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE posts_tags');
    }
}
