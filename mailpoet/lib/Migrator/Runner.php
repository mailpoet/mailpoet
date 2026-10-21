<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Migrations\App\AppMigrationTemplate;
use MailPoet\Migrations\Db\DbMigrationTemplate;
use Throwable;

class Runner {
  private const FATAL_ERROR_TYPES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];

  private const MEMORY_RESERVE_BYTES = 512 * 1024;

  /**
   * Freed in recordFatalError() so the query there has room after an out-of-memory error.
   * One reserve per process; the Monolog and Symfony error handlers use the same technique.
   *
   * @var string|null
   */
  private static $memoryReserve;

  /** @var ContainerWrapper */
  private $container;

  /** @var Store */
  private $store;

  /** @var string|null */
  private $runningMigration;

  /** @var bool */
  private $recordsFatalErrors = false;

  public function __construct(
    ContainerWrapper $container,
    Store $store
  ) {
    $this->container = $container;
    $this->store = $store;
  }

  public function runMigration(string $name, string $level): void {
    $className = $this->getClassName($name, $level);
    if (!$this->recordsFatalErrors) {
      register_shutdown_function([$this, 'recordFatalError']);
      $this->recordsFatalErrors = true;
    }
    if (self::$memoryReserve === null) {
      self::$memoryReserve = str_repeat(' ', self::MEMORY_RESERVE_BYTES);
    }

    try {
      /** @var DbMigration|AppMigration $migration */
      $migration = new $className($this->container);
      $this->store->startMigration($name);
      $this->runningMigration = $name;
      $migration->run();
      $this->runningMigration = null;
      $this->store->completeMigration($name);
    } catch (Throwable $e) {
      $this->runningMigration = null;
      $this->store->failMigration($name, (string)$e);
      throw MigratorException::migrationFailed($className, $e);
    }
  }

  /**
   * A migration that hits the time or memory limit ends the process without an exception,
   * so the catch above never runs and the row would stay "started" with no error. PHP still
   * runs shutdown functions after a fatal error, so the error is recorded from here.
   *
   * Not under WP-CLI: its wp_die handler exits the process from inside WordPress's own
   * fatal error handler, which runs first. The fatal is printed on the terminal instead,
   * and the next web request records it.
   *
   * @param array{type: int, message: string, file: string, line: int}|null $error Defaults to the last PHP error.
   */
  public function recordFatalError(?array $error = null): void {
    if ($this->runningMigration === null) {
      return;
    }
    $error = $error ?? error_get_last();
    if (!$error || !in_array($error['type'], self::FATAL_ERROR_TYPES, true)) {
      return;
    }
    // After an out-of-memory error the process has no room left for the query below.
    self::$memoryReserve = null;
    $this->store->failMigration($this->runningMigration, sprintf('%s in %s:%d', $error['message'], $error['file'], $error['line']));
    $this->runningMigration = null;
  }

  private function getClassName(string $name, string $level): string {
    $templateClass = $level === Repository::MIGRATIONS_LEVEL_DB ? DbMigrationTemplate::class : AppMigrationTemplate::class;
    $className = $this->getNamespace($templateClass) . '\\' . $name;
    if (!class_exists($className)) {
      throw MigratorException::migrationClassNotFound($className);
    }

    $parentClass = $level === Repository::MIGRATIONS_LEVEL_DB ? DbMigration::class : AppMigration::class;
    if (!is_subclass_of($className, $parentClass)) {
      throw MigratorException::migrationClassIsNotASubclassOf($className, $parentClass);
    }
    return $className;
  }

  private function getNamespace(string $className): string {
    $parts = explode('\\', $className);
    return implode('\\', array_slice($parts, 0, -1));
  }
}
