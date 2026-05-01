<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\BigIntType as DoctrineBigIntType;

class BigIntType extends DoctrineBigIntType {
  // override Doctrine's bigint type that historically maps DB's "bigint" to PHP's "string"
  // (we want to map DB's "bigint" to PHP's "int" in today's 64-bit world)
  const NAME = 'bigint';

  public function getBindingType() {
    return ParameterType::INTEGER;
  }

  /**
   * @param mixed $value
   * @return int|null
   */
  public function convertToPHPValue($value, AbstractPlatform $platform) {
    if ($value === null) {
      return null;
    }
    return is_numeric($value) ? (int)$value : 0;
  }

  public function getName() {
    return self::NAME;
  }
}
