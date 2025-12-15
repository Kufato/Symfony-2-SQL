<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203142229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD address LONGTEXT NOT NULL, ADD person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE INDEX IDX_D4E6F81217BBB47 ON address (person_id)');
        $this->addSql('ALTER TABLE bank_account ADD iban VARCHAR(255) DEFAULT NULL, ADD person_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0A217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_53A23E0A217BBB47 ON bank_account (person_id)');
        $this->addSql('ALTER TABLE person ADD username VARCHAR(255) NOT NULL, ADD name VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD enable TINYINT NOT NULL, ADD birthdate DATETIME DEFAULT NULL, ADD marital_status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81217BBB47');
        $this->addSql('DROP INDEX IDX_D4E6F81217BBB47 ON address');
        $this->addSql('ALTER TABLE address DROP address, DROP person_id');
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_53A23E0A217BBB47');
        $this->addSql('DROP INDEX UNIQ_53A23E0A217BBB47 ON bank_account');
        $this->addSql('ALTER TABLE bank_account DROP iban, DROP person_id');
        $this->addSql('ALTER TABLE person DROP username, DROP name, DROP email, DROP enable, DROP birthdate, DROP marital_status');
    }
}
