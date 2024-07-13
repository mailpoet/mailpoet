<?php declare(strict_types = 1);

namespace unit\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Doctrine\WPDB\Exceptions\NotSupportedException;
use MailPoet\Doctrine\WPDB\Statement;
use MailPoetUnitTest;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use stdClass;

class StatementTest extends MailPoetUnitTest {
  public function testBindValue(): void {
    $wpdb = $this->getMockBuilder(stdClass::class)->addMethods(['prepare'])->getMock();
    $wpdb->expects($this->once())
      ->method('prepare')
      ->with('SELECT * FROM test_table WHERE value IN (%s, %d, %d)', ['abc', 123, true])
      ->willReturn('');

    $GLOBALS['wpdb'] = $wpdb;
    $connection = $this->createMock(Connection::class);
    $statement = new Statement($connection, 'SELECT * FROM test_table WHERE value IN (?, ?, ?)');
    $this->assertTrue($statement->bindValue(1, 'abc', ParameterType::STRING));
    $this->assertTrue($statement->bindValue(2, 123, ParameterType::INTEGER));
    $this->assertTrue($statement->bindValue(3, true, ParameterType::BOOLEAN));
    $statement->execute();
  }

  public function testBindParamNotImplemented(): void {
    $this->expectException(NotSupportedException::class);
    $this->expectExceptionMessage('Statement::bindParam() is deprecated in Doctrine and not implemented in WPDB driver. Use Statement::bindValue() instead.');
    $statement = new Statement($this->createMock(Connection::class), 'SELECT * FROM test_table');
    $statement->bindParam(1, $variable);
  }

  public function testExecuteWithParamsNotImplemented(): void {
    $this->expectException(NotSupportedException::class);
    $this->expectExceptionMessage('Statement::execute() with parameters is deprecated in Doctrine and not implemented in WPDB driver. Use Statement::bindValue() instead.');
    $statement = new Statement($this->createMock(Connection::class), 'SELECT * FROM test_table');
    $statement->execute(['param' => 'value']);
  }

  public function testExecuteWithoutParams(): void {
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('query')
      ->with('SELECT * FROM test_table');

    $statement = new Statement($connection, 'SELECT * FROM test_table');
    $statement->execute();
  }

  public function testExecuteWithParams(): void {
    $wpdb = $this->getMockBuilder(stdClass::class)->addMethods(['prepare'])->getMock();
    $wpdb->expects($this->once())
      ->method('prepare')
      ->with('SELECT * FROM test_table WHERE value = %s', ['abc'])
      ->willReturn('');

    $GLOBALS['wpdb'] = $wpdb;
    $connection = $this->createMock(Connection::class);
    $statement = new Statement($connection, 'SELECT * FROM test_table WHERE value = ?');
    $statement->bindValue(1, 'abc');
    $statement->execute();
  }

  /**
   * @dataProvider parameterReplacementProvider
   */
  public function testParameterReplacement(string $inputSql, string $outputSql, int $parameterCount): void {
    $wpdb = $this->getMockBuilder(stdClass::class)->addMethods(['prepare'])->getMock();
    $wpdb->expects($this->once())
      ->method('prepare')
      ->with($outputSql)
      ->willReturn('');

    $GLOBALS['wpdb'] = $wpdb;
    $connection = $this->createMock(Connection::class);
    $statement = new Statement($connection, $inputSql);
    for ($i = 1; $i <= $parameterCount; $i++) {
      $statement->bindValue($i, 'abc');
    }
    $statement->execute();
  }

  public function parameterReplacementProvider(): iterable {
    yield 'simple' => [
      'SELECT * FROM test_table WHERE value = ?',
      'SELECT * FROM test_table WHERE value = %s',
      1,
    ];

    yield 'with ? in string' => [
      "SELECT * FROM test_table WHERE value = ? AND name = 'a?c'",
      "SELECT * FROM test_table WHERE value = %s AND name = 'a?c'",
      1,
    ];

    yield 'with ? in string and multiple parameters' => [
      "SELECT * FROM test_table WHERE value = ? AND name = 'a?c' AND id = ?",
      "SELECT * FROM test_table WHERE value = %s AND name = 'a?c' AND id = %s",
      2,
    ];

    yield 'with JOIN' => [
      'SELECT * FROM test_table JOIN other_table ON test_table.id = other_table.id WHERE value = ?',
      'SELECT * FROM test_table JOIN other_table ON test_table.id = other_table.id WHERE value = %s',
      1,
    ];

    yield 'with subquery' => [
      "SELECT * FROM test_table WHERE value = ? AND name = (SELECT name FROM other_table WHERE id = ?)",
      'SELECT * FROM test_table WHERE value = %s AND name = (SELECT name FROM other_table WHERE id = %s)',
      2,
    ];

    yield 'complex' => [
      "SELECT CONCAT(key, '?') FROM test_table WHERE value = ? AND name = 'a?c' AND id = ? AND (SELECT name FROM other_table WHERE id = ?)",
      "SELECT CONCAT(key, '?') FROM test_table WHERE value = %s AND name = 'a?c' AND id = %s AND (SELECT name FROM other_table WHERE id = %s)",
      3,
    ];
  }
}
