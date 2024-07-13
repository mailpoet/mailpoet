<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoetVendor\Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use MailPoetVendor\Doctrine\DBAL\ParameterType;

class Connection implements ServerInfoAwareConnection {
  public function prepare(string $sql): Statement {
    // TODO: Implement prepare() method.
  }

  public function query(string $sql): Result {
    // TODO: Implement query() method.
  }

  public function exec(string $sql): int {
    // TODO: Implement exec() method.
  }

  public function beginTransaction() {
    // TODO: Implement beginTransaction() method.
  }

  public function commit() {
    // TODO: Implement commit() method.
  }

  public function rollBack() {
    // TODO: Implement rollBack() method.
  }

  public function quote($value, $type = ParameterType::STRING) {
    // TODO: Implement quote() method.
  }

  public function lastInsertId($name = null) {
    // TODO: Implement lastInsertId() method.
  }

  public function getServerVersion() {
    // TODO: Implement getServerVersion() method.
  }

  public function __call($name, $arguments) {
    // TODO: Implement @method object getNativeConnection()
  }
}
