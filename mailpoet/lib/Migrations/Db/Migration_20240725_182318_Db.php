<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;

class Migration_20240725_182318_Db extends DbMigration {
  public function run(): void {
    global $wpdb;
    $automationRunsTable = esc_sql($wpdb->prefix . 'mailpoet_automation_runs');
    $automationRunLogsTable = esc_sql($wpdb->prefix . 'mailpoet_automation_run_logs');

    if (!$this->tableExists($automationRunsTable) || !$this->tableExists($automationRunLogsTable)) {
      return;
    }

    // update failed automation runs that should be complete, but keep updated_at column unchanged
    $this->connection->executeStatement(
      "UPDATE $automationRunsTable
      SET
        `status` = 'complete',
        `updated_at` = `updated_at`
      WHERE id IN (
        SELECT `automation_run_id`
        FROM $automationRunLogsTable
        WHERE `status` = 'failed'
          AND `error` LIKE '%nextStepNotScheduled%'
          AND `step_key` = 'core:if-else'
      )"
    );

    // update failed automation run logs that should be complete, but keep updated_at column unchanged
    $this->connection->executeStatement(
      "UPDATE $automationRunLogsTable
      SET
        `status` = 'complete',
        `error` = NULL,
        `updated_at` = `updated_at`
      WHERE `status` = 'failed'
        AND `error` LIKE '%nextStepNotScheduled%'
        AND `step_key` = 'core:if-else'"
    );
  }
}
