<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Doctrine\WPDB\Exceptions\QueryException;
use MailPoet\Doctrine\WPDB\Result;
use MailPoetTest;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use mysqli;

/**
 * @phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
class ConnectionTest extends MailPoetTest {
  private const TEST_TABLE_NAME = 'doctrine_wpdb_driver_test';

  public function _before() {
    parent::_before();
    $connection = new Connection();
    $connection->exec(sprintf('DROP TABLE IF EXISTS %s', self::TEST_TABLE_NAME));
    $connection->exec(sprintf('
      CREATE TABLE %s (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        value varchar(255) NOT NULL)
    ', self::TEST_TABLE_NAME));
  }

  public function testPrepare(): void {
    $connection = new Connection();

    // prepare data
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));
    $id = $connection->lastInsertId();

    // prepare statement
    $statement = $connection->prepare(sprintf("SELECT value FROM %s WHERE id = ?", self::TEST_TABLE_NAME));
    $statement->bindValue(1, $id, ParameterType::INTEGER);

    // execute
    $result = $statement->execute();
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame('test', $result->fetchOne());

    global $wpdb;
    $this->assertSame(sprintf("SELECT value FROM %s WHERE id = %d", self::TEST_TABLE_NAME, $id), $wpdb->last_query);
  }

  public function testQuery(): void {
    $connection = new Connection();

    // select
    $result = $connection->query('SELECT 123');
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame('123', $result->fetchOne());

    // insert
    $result = $connection->query(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertFalse($result->fetchOne());

    // update
    $result = $connection->query(sprintf("UPDATE %s SET value = 'updated' WHERE id = %d", self::TEST_TABLE_NAME, $connection->lastInsertId()));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertFalse($result->fetchOne());

    // delete
    $result = $connection->query(sprintf("DELETE FROM %s WHERE id = %d", self::TEST_TABLE_NAME, $connection->lastInsertId()));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertFalse($result->fetchOne());
  }

  public function testExec(): void {
    $connection = new Connection();

    // select
    $result = $connection->exec('SELECT 123');
    $this->assertSame(0, $result);

    // insert
    $result = $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));
    $this->assertSame(1, $result);

    // update
    $result = $connection->exec(sprintf("UPDATE %s SET value = 'updated' WHERE id = %d", self::TEST_TABLE_NAME, $connection->lastInsertId()));
    $this->assertSame(1, $result);

    // delete
    $result = $connection->exec(sprintf("DELETE FROM %s WHERE id = %d", self::TEST_TABLE_NAME, $connection->lastInsertId()));
    $this->assertSame(1, $result);
  }

  public function testTransaction(): void {
    $connection = new Connection();

    $this->assertTrue($connection->beginTransaction());
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));

    $this->assertTrue($connection->commit());
    $this->assertSame('1', $connection->query(sprintf('SELECT COUNT(*) FROM %s', self::TEST_TABLE_NAME))->fetchOne());
  }

  public function testTransactionRollBack(): void {
    global $wpdb;
    $tableStatus = (array)$wpdb->get_row($wpdb->prepare("SHOW TABLE STATUS LIKE %s", self::TEST_TABLE_NAME));

    $connection = new Connection();

    $this->assertTrue($connection->beginTransaction());
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));

    $this->assertTrue($connection->rollBack());
    $rowCount = $connection->query(sprintf('SELECT COUNT(*) FROM %s', self::TEST_TABLE_NAME))->fetchOne();
    if ($tableStatus['Engine'] === 'MyISAM') {
      // MyISAM does not support transactions. It ignores transaction commands as noop, so the insert is not rolled back.
      $this->assertSame('1', $rowCount);
    } else {
      $this->assertSame('0', $rowCount);
    }

  }

  public function testQuote(): void {
    $connection = new Connection();
    $this->assertSame("'abc'", $connection->quote('abc'));
    $this->assertSame("'123'", $connection->quote(123));
    $this->assertSame("''", $connection->quote(null));
    $this->assertSame("'abc\\n123'", $connection->quote("abc\n123"));
    $this->assertSame("'abc\\r123'", $connection->quote("abc\r123"));
    $this->assertSame("'abc\\\\123'", $connection->quote('abc\123'));
    $this->assertSame("'abc\'123'", $connection->quote('abc\'123'));
    $this->assertSame("'abc\\\"123'", $connection->quote('abc"123'));
    $this->assertSame("'abc\\0'", $connection->quote("abc\0"));
    $this->assertSame("'abc\\Z123'", $connection->quote("abc\032123"));
    $this->assertSame("'abc\' OR \'1\'=\'1'", $connection->quote("abc' OR '1'='1"));
  }

  public function testLastInsertId(): void {
    $connection = new Connection();
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('one')", self::TEST_TABLE_NAME));
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('two')", self::TEST_TABLE_NAME));
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('three')", self::TEST_TABLE_NAME));
    $autoIncrement = $connection->query(
      sprintf(
        "SELECT auto_increment FROM information_schema.tables WHERE table_schema = '%s' AND table_name = '%s'",
        DB_NAME,
        self::TEST_TABLE_NAME
      )
    )->fetchOne();
    $this->assertSame($autoIncrement - 1, $connection->lastInsertId());
  }

  public function testGetServerVersion(): void {
    $connection = new Connection();
    $mysqli = $connection->getNativeConnection();
    $this->assertInstanceOf(mysqli::class, $mysqli);
    $this->assertSame($mysqli->get_server_info(), $connection->getServerVersion());
  }

  public function testGetNativeConnection(): void {
    $connection = new Connection();
    $this->assertInstanceOf(mysqli::class, $connection->getNativeConnection());
  }

  public function testQueryException(): void {
    $connection = new Connection();

    $exception = null;
    try {
      $connection->exec('SELECT * FROM non_existent_table');
    } catch (QueryException $e) {
      $exception = $e;
    }

    $this->assertInstanceOf(QueryException::class, $exception);
    $this->assertSame(sprintf("Table '%s.non_existent_table' doesn't exist", DB_NAME), $exception->getMessage());
    $this->assertSame('42S02', $exception->getSQLState());
    $this->assertSame(1146, $exception->getCode());
  }

  public function _after() {
    parent::_after();
    $connection = new Connection();
    $connection->exec(sprintf('DROP TABLE IF EXISTS %s', self::TEST_TABLE_NAME));
  }
}
