<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Doctrine\ConnectionFactory;
use MailPoet\Doctrine\SerializableConnection;
use MailPoetVendor\Doctrine\DBAL\Driver\PDOMySql;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;
use PDO;

class ConnectionFactoryTest extends \MailPoetTest {
  function testItSetsUpConnection() {
    $connection_factory = new ConnectionFactory();
    $connection = $connection_factory->createConnection();

    expect($connection)->isInstanceOf(SerializableConnection::class);
    expect($connection->getWrappedConnection())->isInstanceOf(PDO::class);
    expect($connection->getDriver())->isInstanceOf(PDOMySql\Driver::class);
    expect($connection->getDatabasePlatform())->isInstanceOf(MySqlPlatform::class);
    expect($connection->getHost())->equals(Env::$db_host);
    expect($connection->getPort())->equals(Env::$db_port);
    expect($connection->getParams()['socket'])->equals(Env::$db_socket);
    expect($connection->getUsername())->equals(Env::$db_username);
    expect($connection->getPassword())->equals(Env::$db_password);
    expect($connection->getParams()['charset'])->equals(Env::$db_charset);
    expect($connection->getDatabase())->equals(Env::$db_name);
  }

  function testItSetsDriverOptions() {
    $connection_factory = new ConnectionFactory();
    $connection = $connection_factory->createConnection();

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
    expect($result['@@session.sql_mode'])->notContains('ONLY_FULL_GROUP_BY');
    expect($result['@@session.time_zone'])->equals(Env::$db_timezone_offset);
    expect($result['@@session.wait_timeout'])->greaterOrEquals(60);

    // check "SET NAMES ... COLLATE ..."
    expect($result['@@session.character_set_client'])->equals(Env::$db_charset);
    expect($result['@@session.character_set_connection'])->equals(Env::$db_charset);
    expect($result['@@session.character_set_results'])->equals(Env::$db_charset);
    expect($result['@@session.collation_connection'])->equals(Env::$db_collation);
  }

  function testItSelectivelyUpdatesWaitTimeoutOption() {
    // timeout will be kept from DB defaults
    $connection_factory = $this->make(ConnectionFactory::class, [
      'min_wait_timeout' => 1,
    ]);
    $connection = $connection_factory->createConnection();
    $current = $connection->executeQuery('SELECT @@session.wait_timeout')->fetchColumn();
    expect($current)->greaterThan(1);

    // timeout will be set to higher value
    $connection_factory = $this->make(ConnectionFactory::class, [
      'min_wait_timeout' => 999999,
    ]);
    $connection = $connection_factory->createConnection();
    $current = $connection->executeQuery('SELECT @@session.wait_timeout')->fetchColumn();
    expect($current)->equals(999999);
  }
}
