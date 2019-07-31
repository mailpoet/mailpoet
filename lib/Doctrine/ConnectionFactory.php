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
      'wrapperClass' => SerializableConnection::class,
      'driver' => self::DRIVER,
      'platform' => new $platform_class,
      'user' => Env::$db_username,
      'password' => Env::$db_password,
      'dbname' => Env::$db_name,
      'driverOptions' => $this->getDriverOptions(Env::$db_timezone_offset, Env::$db_charset, Env::$db_collation),
    ];

    if (!empty(Env::$db_charset)) {
      $connection_params['charset'] = Env::$db_charset;
    }

    if (!empty(Env::$db_socket)) {
      $connection_params['unix_socket'] = Env::$db_socket;
    } else {
      $connection_params['host'] = Env::$db_is_ipv6 ? ('[' . Env::$db_host . ']') : Env::$db_host;
      $connection_params['port'] = Env::$db_port;
    }

    return DriverManager::getConnection($connection_params);
  }

  private function getDriverOptions($timezone_offset, $charset, $collation) {
    $driver_options = [
      "@@session.time_zone = '$timezone_offset'",
      '@@session.sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")',
      // We need to use CONVERT for MySQL 8, Maria DB bug which triggers #1232 - Incorrect argument type to variable 'wait_timeout`
      // https://stackoverflow.com/questions/35187378/mariadb-type-error-when-setting-session-variable
      "@@session.wait_timeout = GREATEST(CONVERT(COALESCE(@@wait_timeout, 0), SIGNED), $this->min_wait_timeout)",
    ];

    if (!empty(Env::$db_charset)) {
      $driver_options[] = "NAMES $charset" . (empty($collation) ? '' : " COLLATE $collation");
    }

    return [
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET ' . implode(', ', $driver_options),
    ];
  }
}
