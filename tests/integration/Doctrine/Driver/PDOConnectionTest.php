<?php

namespace MailPoet\Doctrine\Driver;

use MailPoet\Doctrine\ConnectionFactory;

require_once __DIR__ . '/DummyUser.php';

class PDOConnectionTest extends \MailPoetTest {

  /** @var PDOConnection */
  private $testConnection;

  public function _before() {
    $this->testConnection = $this->diContainer->get(ConnectionFactory::class)->createConnection();
  }

  public function testItCanQuery() {
    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;");
    expect($statement)->isInstanceOf(PDOStatement::class);

    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;", \PDO::FETCH_COLUMN, 2);
    expect($statement)->isInstanceOf(PDOStatement::class);

    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;", \PDO::FETCH_CLASS, DummyUser::class, ['name', 'age']);
    expect($statement)->isInstanceOf(PDOStatement::class);
  }
}
