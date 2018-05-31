<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180531022557 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE undo_op (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, block_num BIGINT UNSIGNED NOT NULL, transaction_id VARCHAR(40) NOT NULL, op_index INT UNSIGNED NOT NULL, op LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE multi_tasks (id INT UNSIGNED AUTO_INCREMENT NOT NULL, task_type INT UNSIGNED NOT NULL, block_num_from BIGINT UNSIGNED NOT NULL, block_num_to BIGINT UNSIGNED NOT NULL, is_finished TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE undo_op');
        $this->addSql('DROP TABLE multi_tasks');
    }
}
