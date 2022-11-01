<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Repository {
  /** @var string */
  private $migrationsDir;

  /** @var string */
  private $templateFile;

  public function __construct() {
    $this->migrationsDir = __DIR__ . '/../Migrations';
    $this->templateFile = __DIR__ . '/MigrationTemplate.php';
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
    $migration = str_replace('class MigrationTemplate ', "class $name ", $template);
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

  public function loadAll(): array {
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->migrationsDir, RecursiveDirectoryIterator::SKIP_DOTS)
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
