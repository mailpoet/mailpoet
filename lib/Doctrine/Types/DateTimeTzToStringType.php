<?php

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Types\DateTimeTzType;
use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;

class DateTimeTzToStringType extends DateTimeTzType {
  const NAME = 'datetimetz_to_string';

  public function convertToPHPValue($value, AbstractPlatform $platform) {
    $dateTime = parent::convertToPHPValue($value, $platform);

    if (!$dateTime) {
      return $dateTime;
    }

    $value = new Carbon('@' . $dateTime->format('U'));
    $value->setTimezone($dateTime->getTimezone());
    return $value;
  }

  public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
    return true;
  }

  public function getName(): string {
    return self::NAME;
  }
}
