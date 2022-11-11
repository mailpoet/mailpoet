<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Config\Env;
use MailPoet\Migrator\Migration;

class Migration_20221110_151621 extends Migration {
  public function run(): void {
    $prefix = Env::$dbPrefix;
    $charsetCollate = Env::$dbCharsetCollate;

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automations (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        author bigint NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NULL, -- must be NULL, see comment at the top
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        activated_at timestamp NULL,
        deleted_at timestamp NULL,
        PRIMARY KEY (id)
      ) {$charsetCollate};
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_versions (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_id int(11) unsigned NOT NULL,
        steps longtext,
        created_at timestamp NULL, -- must be NULL, see comment at the top
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (automation_id)
      ) {$charsetCollate};
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_triggers (
        automation_id int(11) unsigned NOT NULL,
        trigger_key varchar(255),
        PRIMARY KEY (automation_id, trigger_key)
      );
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_runs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_id int(11) unsigned NOT NULL,
        version_id int(11) unsigned NOT NULL,
        trigger_key varchar(255) NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NULL, -- must be NULL, see comment at the top
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        subjects longtext,
        next_step_id varchar(255),
        PRIMARY KEY (id),
        INDEX (automation_id),
        INDEX (status)
      ) {$charsetCollate};
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_run_logs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_run_id int(11) unsigned NOT NULL,
        step_id varchar(255) NOT NULL,
        status varchar(255) NOT NULL,
        started_at timestamp NOT NULL,
        completed_at timestamp NULL DEFAULT NULL,
        error longtext,
        data longtext,
        PRIMARY KEY (id),
        INDEX (automation_run_id)
      ) {$charsetCollate};
    ");
  }
}
