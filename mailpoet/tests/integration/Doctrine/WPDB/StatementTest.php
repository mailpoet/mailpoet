<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Doctrine\WPDB\Exceptions\QueryException;
use MailPoet\Doctrine\WPDB\Statement;
use MailPoetTest;
use Throwable;

/**
 * @phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
class StatementTest extends MailPoetTest {
  public function testExecuteStatementWithParams(): void {
    $connection = new Connection();
    $statement = new Statement($connection, 'SELECT * FROM test_table WHERE id = ? AND name = :name AND value = ?');
    $statement->bindValue(1, 123);
    $statement->bindValue('name', 'Test');
    $statement->bindValue(2, 'abc');

    $exception = null;
    try {
      $statement->execute();
    } catch (Throwable $e) {
      $exception = $e;
    }

    $this->assertInstanceOf(QueryException::class, $exception);
    $this->assertEquals(sprintf("Table '%s.test_table' doesn't exist", DB_NAME), $exception->getMessage());

    global $wpdb;
    $this->assertSame("SELECT * FROM test_table WHERE id = '123' AND name = 'Test' AND value = 'abc'", $wpdb->last_query);
  }
}
