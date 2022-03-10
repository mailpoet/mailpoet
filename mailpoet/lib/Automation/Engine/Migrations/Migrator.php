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
    $this->prefix = $wpdb->prefix . 'mailpoet_automation_';
    $this->wpdb = $wpdb;
  }

  public function createSchema(): void {
    $this->runQuery("
      CREATE TABLE {$this->prefix}workflows (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        deleted_at timestamp NULL,
        trigger_keys longtext NOT NULL,
        steps longtext,
        PRIMARY KEY (id)
      );
    ");

    $this->runQuery("
      CREATE TABLE {$this->prefix}workflow_runs (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        workflow_id int(11) unsigned NOT NULL,
        trigger_key varchar(255) NOT NULL,
        status varchar(255) NOT NULL,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        subjects longtext,
        PRIMARY KEY (id)
      );
    ");
  }

  public function deleteSchema(): void {
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}workflows");
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}workflow_runs");

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
    $pattern = $this->wpdb->esc_like($this->prefix) . '%';
    return $this->runQuery("SHOW TABLES LIKE '$pattern'") > 0;
  }

  private function runQuery(string $query): int {
    $this->wpdb->hide_errors();
    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::migrationFailed($this->wpdb->last_error ?: 'Unknown error');
    }
    return $result === true ? 0 : (int)$result;
  }
}
