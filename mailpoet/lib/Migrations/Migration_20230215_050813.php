<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Migrator\Migration;

class Migration_20230215_050813 extends Migration {
  public function run(): void {
    $this->subjectsMigration();
    $this->addMetaColumnToAutomations();
  }

  private function addMetaColumnToAutomations(): void {

    global $wpdb;
    $tableName = esc_sql($wpdb->prefix . 'mailpoet_automations');
    if ($this->columnExists($tableName, 'meta')) {
      return;
    }
    $this->connection->executeQuery("ALTER TABLE $tableName ADD COLUMN `meta` LONGTEXT DEFAULT NULL AFTER `status`");
    $this->connection->executeQuery("UPDATE $tableName SET `meta` = '{\"run_automation_once\":true}'");
  }

  private function subjectsMigration(): void {
    $this->createTable('automation_run_subjects', [
      '`automation_run_id` int(11) unsigned NOT NULL',
      '`key` varchar(191)',
      '`args` longtext',
      '`hash` varchar(191)',
      'index (automation_run_id)',
      'index (hash)',
    ]);
    $this->moveSubjectData();
    $this->dropSubjectColumn();
  }

  private function moveSubjectData(): void {
    global $wpdb;
    $runTable = $wpdb->prefix . 'mailpoet_automation_runs';
    $subjectTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
    if (!$this->columnExists($runTable, 'subjects')) {
      return;
    }
    $sql = "SELECT id,subjects FROM $runTable";
    $results = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($results) || !$results) {
      return;
    }

    foreach ($results as $result) {
      $subjects = $result['subjects'];
      if (!$subjects) {
        continue;
      }
      $subjects = json_decode($subjects, true);
      if (!is_array($subjects) || !$subjects) {
        continue;
      }
      $values = [];
      foreach ($subjects as $subject) {
        $values[] = (string)$wpdb->prepare("(%d,%s,%s)", $result['id'], $subject['key'], json_encode($subject['args']));
      }
      $sql = sprintf("INSERT INTO $subjectTable (`automation_run_id`, `key`, `args`) VALUES %s", implode(',', $values));
      if ($wpdb->query($sql) === false) {
        continue;
      }

      $sql = $wpdb->prepare('UPDATE ' . $runTable . ' SET subjects = NULL WHERE id = %d', $result['id']);
      $wpdb->query($sql);
    }
  }

  private function dropSubjectColumn(): void {
    global $wpdb;
    $tableName = esc_sql($wpdb->prefix . 'mailpoet_automation_runs');
    if (!$this->columnExists($tableName, 'subjects')) {
      return;
    }

    $sql = "SELECT id,subjects FROM $tableName where subjects is not null";
    $results = $wpdb->get_results($sql, ARRAY_A);
    if (is_array($results) && $results) {
      return;
    }
    $wpdb->query("ALTER TABLE $tableName DROP COLUMN subjects");
  }
}
