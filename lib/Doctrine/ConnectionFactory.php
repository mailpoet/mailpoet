<?php

namespace MailPoet\Doctrine;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\DBAL\DriverManager;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;
use PDO;

class ConnectionFactory {
  const DRIVER = 'pdo_mysql';
  const PLATFORM_CLASS = MySqlPlatform::class;

  private $min_wait_timeout = 60;

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
      'driverOptions' => $this->getDriverOptions(Env::$db_timezone_offset, Env::$db_charset, Env::$db_collation),
    ];
    return DriverManager::getConnection($connection_params);
  }

  private function getDriverOptions($timezone_offset, $charset, $collation) {
    $driver_options = [
      "@@session.time_zone = '$timezone_offset'",
      '@@session.sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")',
      "@@session.wait_timeout = GREATEST(@@wait_timeout, $this->min_wait_timeout)",
    ];

    if (!empty(Env::$db_charset)) {
      $driver_options[] = "NAMES $charset" . (empty($collation) ? '' : " COLLATE $collation");
    }

    return [
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET ' . implode(', ', $driver_options),
    ];
  }
}
