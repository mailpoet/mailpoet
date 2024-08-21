<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;

class Migration_20230215_050813 extends DbMigration {
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
    $this->connection->executeQuery("UPDATE $tableName SET `meta` = '{\"mailpoet:run-once-per-subscriber\":true}'");
  }

  private function subjectsMigration(): void {
    $this->createTable('automation_run_subjects', [
      '`id` int(11) unsigned NOT NULL AUTO_INCREMENT',
      '`automation_run_id` int(11) unsigned NOT NULL',
      '`key` varchar(191)',
      '`args` longtext',
      '`hash` varchar(191)',
      'PRIMARY KEY  (id)',
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

    $results = $wpdb->get_results($wpdb->prepare("SELECT id,subjects FROM %i", $runTable), ARRAY_A);
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
      if ($wpdb->query($wpdb->prepare("INSERT INTO %i (`automation_run_id`, `key`, `args`) VALUES %s", $subjectTable, implode(',', $values))) === false) {
        continue;
      }

      $wpdb->query($wpdb->prepare("UPDATE %i SET subjects = NULL WHERE id = %d", $runTable, $result['id']));
    }
  }

  private function dropSubjectColumn(): void {
    global $wpdb;
    $tableName = esc_sql($wpdb->prefix . 'mailpoet_automation_runs');
    if (!$this->columnExists($tableName, 'subjects')) {
      return;
    }

    $wpdb->query($wpdb->prepare("ALTER TABLE %i DROP COLUMN subjects", $tableName));
  }
}
