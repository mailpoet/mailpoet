<?php

namespace MailPoet\Doctrine\Driver;

use MailPoet\Doctrine\ConnectionFactory;

require_once __DIR__ . '/DummyUser.php';

class PDOStatementTest extends \MailPoetTest {

  /** @var PDOConnection */
  private $testConnection;

  public function _before() {
    $this->testConnection = $this->diContainer->get(ConnectionFactory::class)->createConnection();
  }

  public function testItCanFetchAll() {
    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;");
    $result = $statement->fetchAll();
    expect($result)->count(1);
    expect($result[0]['name'])->equals('lojza');

    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;");
    $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
    expect($result)->count(1);
    expect($result[0])->equals('lojza');

    $statement = $this->testConnection->query("SELECT 'lojza' as name, 30 as age;");
    $result = $statement->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, DummyUser::class, ['name', 'age']);
    expect($result)->count(1);
    expect($result[0]->getName())->equals('lojza');
  }
}
