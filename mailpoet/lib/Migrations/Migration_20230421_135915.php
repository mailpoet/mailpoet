<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Migrator\Migration;

class Migration_20230421_135915 extends Migration {
  public function run(): void {
    $newslettersTable = $this->getTableName(NewsletterEntity::class);
    $this->connection->executeQuery("
      ALTER TABLE $newslettersTable
      CHANGE type type varchar(150) NOT NULL DEFAULT 'standard'
    ");
    $this->connection->executeQuery("
      UPDATE $newslettersTable
      SET type = 'automation_transactional'
      WHERE type = 'transactional'
    ");
  }
}
