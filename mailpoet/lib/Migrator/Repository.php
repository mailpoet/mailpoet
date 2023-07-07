<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Repository {
  const MIGRATIONS_LEVEL_APP = 'App';
  const MIGRATIONS_LEVEL_DB = 'Db';

  /** @var string */
  private $migrationsDir;

  /** @var string */
  private $templateFile;

  public function __construct() {
    $this->migrationsDir = __DIR__ . '/../Migrations';
    $this->templateFile = __DIR__ . '/DbMigrationTemplate.php';
  }

  public function getMigrationsDir(): string {
    return $this->migrationsDir;
  }

  /** @return array{name: string, path: string} */
  public function create(): array {
    $template = @file_get_contents($this->templateFile);
    if (!$template) {
      throw MigratorException::templateFileReadFailed($this->templateFile);
    }

    $name = $this->generateName();
    $migration = str_replace('class DbMigrationTemplate ', "class $name ", $template);
    $path = $this->migrationsDir . "/$name.php";
    $result = @file_put_contents($path, $migration);
    if (!$result) {
      throw MigratorException::migrationFileWriteFailed($path);
    }
    return [
      'name' => $name,
      'path' => $path,
    ];
  }

  /**
   * Array of filenames.
   * Db migrations are loaded first, then app migrations. This ensures that Db migrator is run before app migrations
   * @return array<string>
   */
  public function loadAll(): array {
    return array_merge(
      $this->loadForLevel(self::MIGRATIONS_LEVEL_DB),
      $this->loadForLevel(self::MIGRATIONS_LEVEL_APP)
    );
  }

  private function loadForLevel(string $level) {
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->migrationsDir . '/' . $level, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $migrations = [];
    foreach ($files as $file) {
      if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
      }
      if (strtolower($file->getFilename()) === 'index.php') {
        continue;
      }
      if (strtolower($file->getExtension()) === 'php') {
        $migrations[] = $file->getBasename('.' . $file->getExtension());
      }
    }
    sort($migrations);
    return $migrations;
  }

  private function generateName(): string {
    return 'Migration_' . gmdate('Ymd_His');
  }
}
