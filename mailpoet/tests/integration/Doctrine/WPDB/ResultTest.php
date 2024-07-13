<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Doctrine\WPDB\Result;
use MailPoetTest;

class ResultTest extends MailPoetTest {
  private const TEST_TABLE_NAME = 'doctrine_wpdb_result_test';

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

  public function testInsert(): void {
    $connection = new Connection();
    $result = $connection->query(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(1, $result->rowCount());
    $this->assertSame(0, $result->columnCount());
    $this->assertSame([], $result->fetchAllAssociative());
  }

  public function testUpdate(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('test')", self::TEST_TABLE_NAME));
    $result = $connection->query(sprintf("UPDATE %s SET value = 'updated' WHERE id = %d", self::TEST_TABLE_NAME, $connection->lastInsertId()));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(1, $result->rowCount());
    $this->assertSame(0, $result->columnCount());
    $this->assertSame([], $result->fetchAllAssociative());
  }

  public function testFetchNumeric(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(['1', 'aaa'], $result->fetchNumeric());
    $this->assertSame(['2', 'bbb'], $result->fetchNumeric());
    $this->assertSame(['3', 'ccc'], $result->fetchNumeric());
    $this->assertFalse($result->fetchNumeric());
  }

  public function testFetchAssociative(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(['id' => '1', 'value' => 'aaa'], $result->fetchAssociative());
    $this->assertSame(['id' => '2', 'value' => 'bbb'], $result->fetchAssociative());
    $this->assertSame(['id' => '3', 'value' => 'ccc'], $result->fetchAssociative());
    $this->assertFalse($result->fetchAssociative());
  }

  public function testFetchOne(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame('1', $result->fetchOne());
    $this->assertSame('2', $result->fetchOne());
    $this->assertSame('3', $result->fetchOne());
    $this->assertFalse($result->fetchOne());
  }

  public function testFetchAllNumeric(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame([['1', 'aaa'], ['2', 'bbb'], ['3', 'ccc']], $result->fetchAllNumeric());
  }

  public function testFetchAllAssociative(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame([
      ['id' => '1', 'value' => 'aaa'],
      ['id' => '2', 'value' => 'bbb'],
      ['id' => '3', 'value' => 'ccc'],
    ], $result->fetchAllAssociative());
  }

  public function testFetchFirstColumn(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(['1', '2', '3'], $result->fetchFirstColumn());
  }

  public function testRowCount(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(3, $result->rowCount());
  }

  public function testColumnCount(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(2, $result->columnCount());
  }

  public function testFree(): void {
    $connection = new Connection();
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('aaa')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('bbb')", self::TEST_TABLE_NAME));
    $connection->query(sprintf("INSERT INTO %s (value) VALUES ('ccc')", self::TEST_TABLE_NAME));

    $result = $connection->query(sprintf("SELECT * FROM %s", self::TEST_TABLE_NAME));
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame('1', $result->fetchOne());
    $this->assertSame('2', $result->fetchOne());
    $this->assertSame('3', $result->fetchOne());
    $this->assertFalse($result->fetchOne());

    $result->free();
    $this->assertSame('1', $result->fetchOne());
  }

  public function _after() {
    parent::_after();
    $connection = new Connection();
    $connection->exec(sprintf('DROP TABLE IF EXISTS %s', self::TEST_TABLE_NAME));
  }
}
