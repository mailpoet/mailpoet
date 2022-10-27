<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Migrations\MigrationTemplate;
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
    $className = $this->namespace . '\\' . $name;
    if (!class_exists($className)) {
      throw MigratorException::migrationClassNotFound($className);
    }

    if (!is_subclass_of($className, Migration::class)) {
      throw MigratorException::migrationClassIsNotASubclassOf($className, Migration::class);
    }

    try {
      $migration = new $className($this->container);
      $this->store->startMigration($name);
      $migration->run();
      $this->store->completeMigration($name);
    } catch (Throwable $e) {
      $this->store->failMigration($name, (string)$e);
      throw MigratorException::migrationFailed($className, $e);
    }
  }

  private function getMigrationsNamespace(): string {
    $parts = explode('\\', MigrationTemplate::class);
    return implode('\\', array_slice($parts, 0, -1));
  }
}
