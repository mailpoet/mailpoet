<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Config\Env;
use MailPoet\Migrator\Migration;

/**
 * The "created_at" column must be NULL in some tables to avoid "there can be only one
 * TIMESTAMP column with CURRENT_TIMESTAMP" error on MySQL version < 5.6.5 that occurs
 * even when other timestamp is simply "NOT NULL".
 *
 * Additionally, having multiple timestamp columns with "NOT NULL" seems to produce the
 * following error in some SQL modes:
 *   SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'updated_at'"
 */
class Migration_20221110_151621 extends Migration {
  public function run(): void {
    $prefix = Env::$dbPrefix;
    $charsetCollate = Env::$dbCharsetCollate;

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automations (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(191) NOT NULL,
        author bigint NOT NULL,
        status varchar(191) NOT NULL,
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
        trigger_key varchar(191),
        PRIMARY KEY (automation_id, trigger_key)
      );
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_runs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_id int(11) unsigned NOT NULL,
        version_id int(11) unsigned NOT NULL,
        trigger_key varchar(191) NOT NULL,
        status varchar(191) NOT NULL,
        created_at timestamp NULL, -- must be NULL, see comment at the top
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        subjects longtext,
        next_step_id varchar(191),
        PRIMARY KEY (id),
        INDEX (automation_id, status)
      ) {$charsetCollate};
    ");

    $this->connection->executeStatement("
      CREATE TABLE {$prefix}automation_run_logs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_run_id int(11) unsigned NOT NULL,
        step_id varchar(191) NOT NULL,
        status varchar(191) NOT NULL,
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
