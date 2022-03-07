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
  }

  public function deleteSchema(): void {
    $this->runQuery("DROP TABLE IF EXISTS {$this->prefix}workflows");
  }

  public function hasSchema(): bool {
    $pattern = str_replace('_', '\\_', $this->prefix) . '%';
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
