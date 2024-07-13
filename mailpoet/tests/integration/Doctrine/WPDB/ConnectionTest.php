<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoetTest;
use mysqli;

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

    $dsn = sprintf('mysql:host=%s;dbname=%s', DB_HOST, DB_NAME);
    $this->pdo = new \PDO($dsn, DB_USER, DB_PASSWORD);
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
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
    $connection = new Connection();

    $this->assertTrue($connection->beginTransaction());
    $connection->exec(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));

    $this->assertTrue($connection->rollBack());
    $this->assertSame('0', $connection->query(sprintf('SELECT COUNT(*) FROM %s', self::TEST_TABLE_NAME))->fetchOne());
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

  public function _after() {
    parent::_after();
    $connection = new Connection();
    $connection->exec(sprintf('DROP TABLE IF EXISTS %s', self::TEST_TABLE_NAME));
  }
}
