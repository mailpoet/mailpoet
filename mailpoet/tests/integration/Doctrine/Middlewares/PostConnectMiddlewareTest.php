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
}
