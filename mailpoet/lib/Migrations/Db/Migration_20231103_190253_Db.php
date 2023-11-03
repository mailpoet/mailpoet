<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;

class Migration_20231103_190253_Db extends DbMigration {
  public function run(): void {
    $this->createTable('subscriber_engagement_search', [
      '`id` int(11) unsigned NOT NULL AUTO_INCREMENT',
      'PRIMARY KEY  (id)',
    ]);
  }
}
