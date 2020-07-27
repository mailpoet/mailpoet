<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Doctrine\ConnectionFactory;
use MailPoet\Doctrine\SerializableConnection;
use MailPoet\Doctrine\Types\JsonOrSerializedType;
use MailPoet\Doctrine\Types\JsonType;
use MailPoetVendor\Doctrine\DBAL\Driver\PDOMySql;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\Type;
use PDO;

class ConnectionFactoryTest extends \MailPoetTest {
  private $envBackup = [];

  public function _before() {
    parent::_before();
    $this->envBackup['db_host'] = Env::$dbHost;
    $this->envBackup['db_is_ipv6'] = Env::$dbIsIpv6;
    $this->envBackup['db_port'] = Env::$dbPort;
    $this->envBackup['db_socket'] = Env::$dbSocket;
    $this->envBackup['db_charset'] = Env::$dbCharset;
  }

  public function testItSetsUpConnection() {
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();

    expect($connection)->isInstanceOf(SerializableConnection::class);
    expect($connection->getWrappedConnection())->isInstanceOf(PDO::class);
    expect($connection->getDriver())->isInstanceOf(PDOMySql\Driver::class);
    expect($connection->getDatabasePlatform())->isInstanceOf(MySqlPlatform::class);
    expect($connection->getHost())->equals(Env::$dbHost);
    expect($connection->getPort())->equals(Env::$dbPort);
    expect($connection->getParams())->notContains('unix_socket');
    expect($connection->getUsername())->equals(Env::$dbUsername);
    expect($connection->getPassword())->equals(Env::$dbPassword);
    expect($connection->getParams()['charset'])->equals(Env::$dbCharset);
    expect($connection->getDatabase())->equals(Env::$dbName);

    expect(Type::getType(JsonType::NAME))->isInstanceOf(JsonType::class);
    expect(Type::getType(JsonOrSerializedType::NAME))->isInstanceOf(JsonOrSerializedType::class);
  }

  public function testItIgnoresEmptyCharset() {
    Env::$dbCharset = '';
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();
    expect($connection->getParams())->hasntKey('charset');
  }

  public function testItSetsUpSocket() {
    Env::$dbHost = null;
    Env::$dbPort = null;
    Env::$dbSocket = 'socket';
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();

    expect($connection->getHost())->null();
    expect($connection->getPort())->null();
    expect($connection->getParams()['unix_socket'])->equals('socket');
  }

  public function testItSetsUpIpV6() {
    Env::$dbIsIpv6 = true;

    Env::$dbHost = '::1';
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();
    expect($connection->getHost())->equals('[::1]');

    Env::$dbHost = 'b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036';
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();
    expect($connection->getHost())->equals('[b57e:9b70:ab96:6a0b:5ba2:49e3:ebba:a036]');

    // try to actually connect to the DB over IPv6
    Env::$dbHost = '::ffff:' . gethostbyname($this->envBackup['db_host']);
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();
    expect($connection->getWrappedConnection())->isInstanceOf(PDO::class);
    expect($connection->executeQuery('SELECT 1')->fetchColumn())->same('1');
  }

  public function testItSetsDriverOptions() {
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();

    $result = $connection->executeQuery('
      SELECT
        @@session.sql_mode,
        @@session.time_zone,
        @@session.wait_timeout,
        @@session.character_set_client,
        @@session.character_set_connection,
        @@session.character_set_results,
        @@session.collation_connection
    ')->fetch();

    // check timezone, SQL mode, wait timeout
    expect($result['@@session.time_zone'])->equals(Env::$dbTimezoneOffset);
    expect($result['@@session.wait_timeout'])->greaterOrEquals(60);

    // check "SET NAMES ... COLLATE ..."
    expect($result['@@session.character_set_client'])->equals(Env::$dbCharset);
    expect($result['@@session.character_set_connection'])->equals(Env::$dbCharset);
    expect($result['@@session.character_set_results'])->equals(Env::$dbCharset);
    expect($result['@@session.collation_connection'])->equals(Env::$dbCollation);
  }

  public function testItSelectivelyUpdatesWaitTimeoutOption() {
    // timeout will be kept from DB defaults
    $connectionFactory = $this->make(ConnectionFactory::class, [
      'minWaitTimeout' => 1,
    ]);
    $connection = $connectionFactory->createConnection();
    $current = $connection->executeQuery('SELECT @@session.wait_timeout')->fetchColumn();
    expect($current)->greaterThan(1);

    // timeout will be set to higher value
    $connectionFactory = $this->make(ConnectionFactory::class, [
      'minWaitTimeout' => 999999,
    ]);
    $connection = $connectionFactory->createConnection();
    $current = $connection->executeQuery('SELECT @@session.wait_timeout')->fetchColumn();
    expect($current)->equals(999999);
  }

  public function _after() {
    parent::_after();
    Env::$dbHost = $this->envBackup['db_host'];
    Env::$dbPort = $this->envBackup['db_port'];
    Env::$dbIsIpv6 = $this->envBackup['db_is_ipv6'];
    Env::$dbSocket = $this->envBackup['db_socket'];
    Env::$dbCharset = $this->envBackup['db_charset'];
  }
}
