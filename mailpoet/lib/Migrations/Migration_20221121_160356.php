<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Config\Env;
use MailPoet\Migrator\Migration;

class Migration_20221121_160356 extends Migration {

  /*** @var string */
  private $prefix;

  public function run(): void {
    $this->prefix = Env::$dbPrefix;

    $this->addMethodUnsubscribeStats();
  }

  private function addMethodUnsubscribeStats() {
    global $wpdb;

    $tableName = esc_sql("{$this->prefix}statistics_unsubscribes");

    // Add method column in case it doesn't exist
    $methodColumnExists = $wpdb->get_results($wpdb->prepare("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_name = %s AND column_name = 'method';
     ", $tableName));

    if (empty($methodColumnExists)) {
      $query = "
      ALTER TABLE `{$tableName}`
        ADD `method` varchar(40) NOT NULL DEFAULT 'unknown';
      ";
      $wpdb->query($query);
    }
    return true;
  }
}
