<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoetVendor\Doctrine\DBAL\Driver\Result;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement as StatementInterface;
use MailPoetVendor\Doctrine\DBAL\ParameterType;

class Statement implements StatementInterface {
  public function bindValue($param, $value, $type = ParameterType::STRING) {
    // TODO: Implement bindValue() method.
  }

  public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null) {
    // TODO: Implement bindParam() method.
  }

  public function execute($params = null): Result {
    // TODO: Implement execute() method.
  }
}
