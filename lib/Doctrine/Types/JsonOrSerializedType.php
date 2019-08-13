<?php

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;

class JsonOrSerializedType extends JsonType {
  const NAME = 'json_or_serialized';

  function convertToPHPValue($value, AbstractPlatform $platform) {
    if ($value === null) {
      return null;
    }

    if (is_resource($value)) {
      $value = stream_get_contents($value);
    }

    if (is_serialized($value)) {
      return unserialize($value);
    }
    return parent::convertToPHPValue($value, $platform);
  }

  function getName() {
    return self::NAME;
  }
}
