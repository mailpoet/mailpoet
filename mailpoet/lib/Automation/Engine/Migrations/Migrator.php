<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Migrations;

use MailPoet\Automation\Engine\Exceptions;
use wpdb;

class Migrator {
  /** @var string */
  private $prefix;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->prefix = $wpdb->prefix . 'mailpoet_';
    $this->wpdb = $wpdb;
  }

  public function createSchema(): void {
    $this->removeOldSchema();

    $this->runQuery("
      CREATE TABLE {$this->prefix}automations (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        author bigint NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        activated_at timestamp NULL,
        deleted_at timestamp NULL,
        PRIMARY KEY (id)
      );
    ");

    $this->runQuery("
      CREATE TABLE {$this->prefix}automation_versions (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_id int(11) unsigned NOT NULL,
        steps longtext,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        PRIMARY KEY (id),
        INDEX (automation_id)
      );
    ");

    $this->runQuery("
      CREATE TABLE {$this->prefix}automation_triggers (
        automation_id int(11) unsigned NOT NULL,
        trigger_key varchar(255),
        PRIMARY KEY (automation_id, trigger_key)
      );
    ");

    $this->runQuery("
      CREATE TABLE {$this->prefix}automation_runs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        automation_id int(11) unsigned NOT NULL,
        version_id int(11) unsigned NOT NULL,
        trigger_key varchar(255) NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        subjects longtext,
        next_step_id varchar(255),
        PRIMARY KEY (id),
        INDEX (automation_id),
        INDEX (status)
      );
    ");

    $this->runQuery("
      CREATE TABLE {$this->prefix}automation_run_logs (
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
      );
    ");
  }

  public function deleteSchema(): void {
    $this->removeOldSchema();
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}automations");
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}automation_runs");
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}automation_run_logs");
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}automation_versions");
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}automation_triggers");

    // clean Action Scheduler data
    $this->runQuery("
      DELETE FROM {$this->wpdb->prefix}actionscheduler_claims WHERE claim_id IN (
        SELECT claim_id FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook LIKE '%mailpoet/automation%'
      )
    ");
    $this->runQuery("
      DELETE FROM {$this->wpdb->prefix}actionscheduler_logs WHERE action_id IN (
        SELECT action_id FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook LIKE '%mailpoet/automation%'
      )
    ");
    $this->runQuery("DELETE FROM {$this->wpdb->prefix}actionscheduler_actions WHERE hook LIKE '%mailpoet/automation%'");
    $this->runQuery("DELETE FROM {$this->wpdb->prefix}actionscheduler_groups WHERE slug = 'mailpoet-automation'");
  }

  public function hasSchema(): bool {
    $pattern = $this->wpdb->esc_like("{$this->prefix}automations") . '%';
    return $this->runQuery("SHOW TABLES LIKE '$pattern'") > 0;
  }

  private function removeOldSchema(): void {
    $oldPrefix = $this->wpdb->prefix . 'mailpoet_automation_';
    $this->runQuery("DROP TABLE IF EXISTS {$oldPrefix}automations");
    $this->runQuery("DROP TABLE IF EXISTS {$oldPrefix}automation_runs");
  }

  private function runQuery(string $query): int {
    $this->wpdb->hide_errors();
    // It's a private method and all Queries in this class are safe
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    $result = $this->wpdb->query($query);

    if ($result === false) {
      throw Exceptions::migrationFailed($this->wpdb->last_error ?: 'Unknown error');
    }
    return $result === true ? 0 : (int)$result;
  }
}
