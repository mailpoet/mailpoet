<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\ConnectionException;
use MailPoet\Doctrine\WPDB\Exceptions\QueryException;
use MailPoetVendor\Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use mysqli;
use PDO;
use PDOException;
use Throwable;
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

  /**
   * MySQL — returns an instance of mysqli.
   * SQLite — returns an instance of PDO.
   *
   * @return mysqli|PDO|false|null
   */
  public function getNativeConnection() {
    global $wpdb;

    // WPDB keeps connection instance (mysqli) in a protected property $dbh.
    // We can access it using a closure that is bound to the $wpdb instance.
    $getDbh = function () {
      return $this->dbh; // @phpstan-ignore-line -- PHPStan doesn't know the binding context
    };
    $dbh = $getDbh->call($wpdb);
    if (is_object($dbh) && method_exists($dbh, 'get_pdo')) {
      return $dbh->get_pdo();
    }
    return $getDbh->call($wpdb);
  }

  public static function isSQLite(): bool {
    return defined('DB_ENGINE') && DB_ENGINE === 'sqlite';
  }

  private function runQuery(string $sql) {
    global $wpdb;
    try {
      $value = $wpdb->query($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } catch (Throwable $e) {
      if ($e instanceof PDOException) {
        throw new QueryException($e->getMessage(), $e->errorInfo[0] ?? null, $e->errorInfo[1] ?? 0);
      }
      throw new QueryException($e->getMessage(), null, 0, $e);
    }
    if ($value === false) {
      $this->handleQueryError();
    }
    return $value;
  }

  private function handleQueryError(): void {
    global $wpdb;
    $nativeConnection = $this->getNativeConnection();
    if ($nativeConnection instanceof mysqli) {
      throw new QueryException($wpdb->last_error, $nativeConnection->sqlstate, $nativeConnection->errno);
    } elseif ($nativeConnection instanceof PDO) {
      $info = $nativeConnection->errorInfo();
      throw new QueryException($wpdb->last_error, $info[0] ?? null, $info[1] ?? 0);
    }
    throw new QueryException($wpdb->last_error);
  }
}
