<?php

namespace MailPoetTasks\Release;

class VersionHelper {
  const VERSION_REGEXP = '/^(\d+)\.(\d+)\.(\d+)$/';

  const MAJOR = 'Major';
  const MINOR = 'Minor';
  const PATCH = 'Patch';

  static function parseVersion($version) {
    if (!preg_match(self::VERSION_REGEXP, $version, $matches)) {
      throw new \Exception('Incorrect version format');
    }
    return [
      self::MAJOR => $matches[1],
      self::MINOR => $matches[2],
      self::PATCH => $matches[3],
    ];
  }

  static function buildVersion(array $parts) {
    return sprintf('%d.%d.%d', $parts[self::MAJOR], $parts[self::MINOR], $parts[self::PATCH]);
  }

  static function validateVersion($version) {
    return preg_match(self::VERSION_REGEXP, $version);
  }
}
