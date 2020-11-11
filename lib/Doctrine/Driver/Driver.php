<?php

namespace MailPoet\Doctrine\Driver;

use MailPoetVendor\Doctrine\DBAL\DBALException;

class Driver extends \MailPoetVendor\Doctrine\DBAL\Driver\PDOMySql\Driver {
  public function connect(array $params, $username = null, $password = null, array $driverOptions = []) {
    try {
      $conn = new PDOConnection(
        $this->constructPdoDsn($params),
        $username,
        $password,
        $driverOptions
      );
    } catch (\PDOException $e) {
      throw DBALException::driverException($this, $e);
    }

    return $conn;
  }
}
