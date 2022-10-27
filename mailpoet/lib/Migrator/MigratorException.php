<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\InvalidStateException;

class MigratorException extends InvalidStateException {
  public static function templateFileReadFailed(string $path): self {
    return self::create()->withMessage(
      sprintf('Could not read migration template file "%s".', $path)
    );
  }

  public static function migrationFileWriteFailed(string $path): self {
    return self::create()->withMessage(
      sprintf('Could not write migration file "%s".', $path)
    );
  }
}
