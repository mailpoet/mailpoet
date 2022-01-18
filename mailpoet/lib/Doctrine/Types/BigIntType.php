<?php

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\BigIntType as DoctrineBigIntType;
use PDO;

class BigIntType extends DoctrineBigIntType {
  // override Doctrine's bigint type that historically maps DB's "bigint" to PHP's "string"
  // (we want to map DB's "bigint" to PHP's "int" in today's 64-bit world)
  const NAME = 'bigint';

  public function getBindingType() {
    return PDO::PARAM_INT;
  }

  public function convertToPHPValue($value, AbstractPlatform $platform) {
    return $value === null ? null : (int)$value;
  }

  public function getName() {
    return self::NAME;
  }
}
