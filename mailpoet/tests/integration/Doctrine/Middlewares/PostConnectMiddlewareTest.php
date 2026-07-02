<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\Middlewares;

use MailPoet\Doctrine\ConnectionFactory;

class PostConnectMiddlewareTest extends \MailPoetTest {
  public function testItEnablesBigSelectsOnConnect() {
    $connection = (new ConnectionFactory())->createConnection();
    $bigSelects = $connection->fetchOne('SELECT @@SESSION.sql_big_selects');
    verify($bigSelects)->equals('1');
  }

  public function testItEnablesBigSelectsOnTheRuntimeConnection() {
    $bigSelects = $this->entityManager->getConnection()->fetchOne('SELECT @@SESSION.sql_big_selects');
    verify($bigSelects)->equals('1');
  }

  public function testItUsesUtcDatabaseTimezoneWithInvalidWordPressOffset() {
    $originalOffset = get_option('gmt_offset');
    update_option('gmt_offset', -15);

    try {
      $connection = (new ConnectionFactory())->createConnection();
      $databaseTimezone = $connection->fetchOne('SELECT @@SESSION.time_zone');
    } finally {
      update_option('gmt_offset', $originalOffset);
    }

    verify($databaseTimezone)->equals('+00:00');
  }
}
