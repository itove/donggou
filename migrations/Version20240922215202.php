<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240922215202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE "check_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "check" (id INT NOT NULL, ord_id INT NOT NULL, checker_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3C8EAC13E636D3F5 ON "check" (ord_id)');
        $this->addSql('CREATE INDEX IDX_3C8EAC1377637F8F ON "check" (checker_id)');
        $this->addSql('COMMENT ON COLUMN "check".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE "check" ADD CONSTRAINT FK_3C8EAC13E636D3F5 FOREIGN KEY (ord_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "check" ADD CONSTRAINT FK_3C8EAC1377637F8F FOREIGN KEY (checker_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE "check_id_seq" CASCADE');
        $this->addSql('ALTER TABLE "check" DROP CONSTRAINT FK_3C8EAC13E636D3F5');
        $this->addSql('ALTER TABLE "check" DROP CONSTRAINT FK_3C8EAC1377637F8F');
        $this->addSql('DROP TABLE "check"');
    }
}
