<?php

namespace MailPoet\Doctrine;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\DBAL\Configuration;
use MailPoetVendor\Doctrine\DBAL\DriverManager;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;

class ConnectionFactory {
  const DRIVER = 'pdo_mysql';
  const PLATFORM_CLASS = MySqlPlatform::class;

  function createConnection() {
    $platform_class = self::PLATFORM_CLASS;
    $connection_params = [
      'driver' => self::DRIVER,
      'platform' => new $platform_class,
      'host' => Env::$db_host,
      'port' => Env::$db_port,
      'socket' => Env::$db_socket,
      'user' => Env::$db_username,
      'password' => Env::$db_password,
      'charset' => Env::$db_charset,
      'dbname' => Env::$db_name,
    ];
    return DriverManager::getConnection($connection_params);
  }
}
