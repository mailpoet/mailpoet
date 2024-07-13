<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\ConnectionException;
use MailPoet\Doctrine\WPDB\Exceptions\QueryException;
use MailPoetVendor\Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use mysqli;
use wpdb;

/**
 * @phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
class Connection implements ServerInfoAwareConnection {
  public function __construct() {
    global $wpdb;
    if (!$wpdb instanceof wpdb) {
      throw new ConnectionException('WPDB is not initialized.');
    }
  }

  public function prepare(string $sql): Statement {
    return new Statement($this, $sql);
  }

  public function query(string $sql): Result {
    global $wpdb;
    $value = $this->runQuery($sql);
    $result = $wpdb->last_result;
    return new Result($result, is_int($value) ? $value : 0);
  }

  public function exec(string $sql): int {
    global $wpdb;
    $this->runQuery($sql);
    return $wpdb->rows_affected;
  }

  public function beginTransaction(): bool {
    $this->runQuery('START TRANSACTION');
    return true;
  }

  public function commit(): bool {
    $this->runQuery('COMMIT');
    return true;
  }

  public function rollBack(): bool {
    $this->runQuery('ROLLBACK');
    return true;
  }

  /**
   * Quotes a string for use in a query.
   * The type hint parameter is not needed for WPDB (mysqli).
   * See also Doctrine\DBAL\Driver\Mysqli\Connection::quote().
   *
   * @param mixed $value
   * @param int $type
   */
  public function quote($value, $type = ParameterType::STRING): string {
    global $wpdb;
    return "'" . $wpdb->_escape($value) . "'";
  }

  /**
   * @param string|null $name
   */
  public function lastInsertId($name = null): int {
    global $wpdb;
    return $wpdb->insert_id;
  }

  public function getServerVersion(): string {
    global $wpdb;
    return $wpdb->db_server_info();
  }

  /** @return mysqli|false|null */
  public function getNativeConnection() {
    global $wpdb;

    // WPDB keeps connection instance (mysqli) in a protected property $dbh.
    // We can access it using a closure that is bound to the $wpdb instance.
    $getConnection = function () {
      return $this->dbh; // @phpstan-ignore-line -- PHPStan doesn't know the binding context
    };
    return $getConnection->call($wpdb);
  }

  private function runQuery(string $sql) {
    global $wpdb;
    $value = $wpdb->query($sql); // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    if ($value === false) {
      throw new QueryException($wpdb->last_error);
    }
    return $value;
  }
}
