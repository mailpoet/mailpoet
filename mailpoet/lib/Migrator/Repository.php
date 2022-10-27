<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

class Repository {
  /** @var string */
  private $migrationsDir;

  /** @var string */
  private $templateFile;

  public function __construct() {
    $this->migrationsDir = __DIR__ . '/../Migrations';
    $this->templateFile = __DIR__ . '/MigrationTemplate.php';
  }

  public function create(): void {
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
  }

  private function generateName(): string {
    return 'Migration_' . gmdate('Ymd_his');
  }
}
