<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Migrator\Migration;

class Migration_20230203_065303 extends Migration {
  public function run(): void {

    global $wpdb;
    $tableName = $wpdb->prefix . 'mailpoet_automation_runs';
    if ($this->columnExists($tableName, 'subject_hash')) {
      return;
    }
    $this->connection->executeQuery("ALTER TABLE {$tableName} ADD `subject_hash` VARCHAR(191) NOT NULL DEFAULT '' AFTER `next_step_id`, ADD INDEX `subject_hash` (`subject_hash`);");
  }
}
