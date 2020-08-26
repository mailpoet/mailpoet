<?php

namespace MailPoet\Doctrine;

use MailPoet\Config\Env;
use MailPoet\Doctrine\Types\BigIntType;
use MailPoet\Doctrine\Types\JsonOrSerializedType;
use MailPoet\Doctrine\Types\JsonType;
use MailPoet\Doctrine\Types\SerializedArrayType;
use MailPoetVendor\Doctrine\DBAL\DriverManager;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySqlPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\Type;
use PDO;

class ConnectionFactory {
  const DRIVER = 'pdo_mysql';
  const PLATFORM_CLASS = MySqlPlatform::class;

  private $minWaitTimeout = 60;

  private $types = [
    BigIntType::NAME => BigIntType::class,
    JsonType::NAME => JsonType::class,
    JsonOrSerializedType::NAME => JsonOrSerializedType::class,
    SerializedArrayType::NAME => SerializedArrayType::class,
  ];

  public function createConnection() {
    $platformClass = self::PLATFORM_CLASS;
    $connectionParams = [
      'wrapperClass' => SerializableConnection::class,
      'driver' => self::DRIVER,
      'platform' => new $platformClass,
      'user' => Env::$dbUsername,
      'password' => Env::$dbPassword,
      'dbname' => Env::$dbName,
      'driverOptions' => $this->getDriverOptions(Env::$dbTimezoneOffset, Env::$dbCharset, Env::$dbCollation),
    ];

    if (!empty(Env::$dbCharset)) {
      $connectionParams['charset'] = Env::$dbCharset;
    }

    if (!empty(Env::$dbSocket)) {
      $connectionParams['unix_socket'] = Env::$dbSocket;
    } else {
      $connectionParams['host'] = Env::$dbIsIpv6 ? ('[' . Env::$dbHost . ']') : Env::$dbHost;
      $connectionParams['port'] = Env::$dbPort;
    }

    $this->setupTypes();
    return DriverManager::getConnection($connectionParams);
  }

  private function getDriverOptions($timezoneOffset, $charset, $collation) {
    $driverOptions = [
      "@@session.time_zone = '$timezoneOffset'",
      '@@session.sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")', // This is needed because ONLY_FULL_GROUP_BY mode in MariaDB is much more restrictive than in MySQL
      // We need to use CONVERT for MySQL 8, Maria DB bug which triggers #1232 - Incorrect argument type to variable 'wait_timeout`
      // https://stackoverflow.com/questions/35187378/mariadb-type-error-when-setting-session-variable
      "@@session.wait_timeout = GREATEST(CONVERT(COALESCE(@@wait_timeout, 0), SIGNED), $this->minWaitTimeout)",
    ];

    if (!empty(Env::$dbCharset)) {
      $driverOptions[] = "NAMES $charset" . (empty($collation) ? '' : " COLLATE $collation");
    }

    return [
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET ' . implode(', ', $driverOptions),
    ];
  }

  private function setupTypes() {
    foreach ($this->types as $name => $class) {
      if (Type::hasType($name)) {
        Type::overrideType($name, $class);
      } else {
        Type::addType($name, $class);
      }
    }
  }
}
