<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Migrations\DbMigrationTemplate;
use Throwable;

class Runner {
  /** @var ContainerWrapper */
  private $container;

  /** @var Store */
  private $store;

  /** @var string */
  private $namespace;

  public function __construct(
    ContainerWrapper $container,
    Store $store
  ) {
    $this->container = $container;
    $this->store = $store;
    $this->namespace = $this->getMigrationsNamespace();
  }

  public function runMigration(string $name): void {
    $className = $this->getClassName($name);

    try {
      $migration = new $className($this->container);
      $this->store->startMigration($name);
      // PHPStan does not recognize that $migration is instance of DbMigration or AppMigration
      /** @phpstan-ignore-next-line */
      $migration->run();
      $this->store->completeMigration($name);
    } catch (Throwable $e) {
      $this->store->failMigration($name, (string)$e);
      throw MigratorException::migrationFailed($className, $e);
    }
  }

  private function getClassName(string $name): string {
    $level = Repository::MIGRATIONS_LEVEL_DB;
    $className = $this->namespace . '\\' . $level . '\\' . $name;
    if (!class_exists($className)) {
      $level = Repository::MIGRATIONS_LEVEL_APP;
      $className = $this->namespace . '\\' . $level . '\\' . $name;
    }

    if (!class_exists($className)) {
      throw MigratorException::migrationClassNotFound($className);
    }

    $parentClass = $level === Repository::MIGRATIONS_LEVEL_DB ? DbMigration::class : AppMigration::class;
    if (!is_subclass_of($className, $parentClass)) {
      throw MigratorException::migrationClassIsNotASubclassOf($className, $parentClass);
    }
    return $className;
  }

  private function getMigrationsNamespace(): string {
    $parts = explode('\\', DbMigrationTemplate::class);
    return implode('\\', array_slice($parts, 0, -1));
  }
}
