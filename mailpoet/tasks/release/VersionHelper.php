<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class VersionHelper {
  const VERSION_REGEXP = '/^(\d+)\.(\d+)\.(\d+)$/';

  const MAJOR = 'Major';
  const MINOR = 'Minor';
  const PATCH = 'Patch';

  public static function incrementVersion($version, $partToIncrement = self::PATCH) {
    $parsedVersion = is_array($version) ? $version : self::parseVersion($version);

    switch ($partToIncrement) {
      case self::MINOR:
        $parsedVersion[self::MINOR]++;
        $parsedVersion[self::PATCH] = 0;
        break;
      case self::PATCH:
      default:
        $parsedVersion[self::PATCH]++;
        break;
    }

    return is_array($version) ? $parsedVersion : self::buildVersion($parsedVersion);
  }

  public static function parseVersion($version) {
    if (!preg_match(self::VERSION_REGEXP, $version, $matches)) {
      throw new \Exception('Incorrect version format');
    }
    return [
      self::MAJOR => $matches[1],
      self::MINOR => $matches[2],
      self::PATCH => $matches[3],
    ];
  }

  public static function buildVersion(array $parts) {
    return sprintf('%d.%d.%d', $parts[self::MAJOR], $parts[self::MINOR], $parts[self::PATCH]);
  }

  public static function buildMinorVersion(array $parts) {
    return sprintf('%d.%d', $parts[self::MAJOR], $parts[self::MINOR]);
  }

  public static function validateVersion($version) {
    return preg_match(self::VERSION_REGEXP, $version);
  }
}
