<?php

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\Type;

class JsonType extends Type {
  const NAME = 'json';

  function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
    return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
  }

  function convertToDatabaseValue($value, AbstractPlatform $platform) {
    if ($value === null) {
      return null;
    }

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_PRESERVE_ZERO_FRACTION')) {
      $flags |= JSON_PRESERVE_ZERO_FRACTION; // phpcs:ignore
    }

    $encoded = json_encode($value, $flags);
    $this->handleErrors();
    return $encoded;
  }

  function convertToPHPValue($value, AbstractPlatform $platform) {
    if ($value === null) {
      return null;
    }

    if (is_resource($value)) {
      $value = stream_get_contents($value);
    }

    $decoded = json_decode($value, true);
    $this->handleErrors();
    return $decoded;
  }

  function getName() {
    return self::NAME;
  }

  function requiresSQLCommentHint(AbstractPlatform $platform) {
    return !$platform->hasNativeJsonType();
  }

  private function handleErrors() {
    $error = json_last_error();
    if ($error !== JSON_ERROR_NONE) {
      throw new \RuntimeException(json_last_error_msg(), $error);
    }
  }
}
