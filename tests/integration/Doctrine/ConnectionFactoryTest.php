<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Doctrine\ConnectionFactory;
use MailPoetVendor\Doctrine\DBAL\Driver\PDOMySql;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;
use PDO;

class ConnectionFactoryTest extends \MailPoetTest {
  function testItSetsUpConnection() {
    $connection_factory = new ConnectionFactory();
    $connection = $connection_factory->createConnection();

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
}
