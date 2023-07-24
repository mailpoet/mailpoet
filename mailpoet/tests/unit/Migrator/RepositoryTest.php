<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoetUnitTest;

class RepositoryTest extends MailPoetUnitTest {
  private const MIGRATIONS_OUTPUT_DIR = __DIR__ . '/../../_output/migrations-tests';
  private const TEMPLATE_FILE = __DIR__ . '/../../../lib/Migrator/{level}MigrationTemplate.php';

  protected function _before() {
    parent::_before();
    $this->removeDir(self::MIGRATIONS_OUTPUT_DIR);
    mkdir(self::MIGRATIONS_OUTPUT_DIR, 0777, true);
    mkdir(self::MIGRATIONS_OUTPUT_DIR . '/' . Repository::MIGRATIONS_LEVEL_DB, 0777, true);
    mkdir(self::MIGRATIONS_OUTPUT_DIR . '/' . Repository::MIGRATIONS_LEVEL_APP, 0777, true);
  }

  public function testItCreatesDbLevelMigrationFiles(): void {
    $this->checkItCreatesMigrationFiles(Repository::MIGRATIONS_LEVEL_DB);
  }

  public function testItCreatesAppLevelMigrationFiles(): void {
    $this->checkItCreatesMigrationFiles(Repository::MIGRATIONS_LEVEL_APP);
  }

  public function testItFailsCreatingWithInvalidTemplateFile(): void {
    $templateFile = __DIR__ . '/NonExistentTemplateFile.php';
    $repository = $this->make(Repository::class, [
      'templateFile' => $templateFile,
    ]);

    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage(sprintf('Could not read migration template file "%s"', $templateFile));
    $repository->create(Repository::MIGRATIONS_LEVEL_DB);
  }

  public function testItFailsCreatingWhenCantSave(): void {
    $migrationsDir = __DIR__ . '/Non/Existent/Directory';
    $repository = $this->make(Repository::class, [
      'migrationsDir' => $migrationsDir,
      'templateFile' => self::TEMPLATE_FILE,
    ]);

    $this->expectException(MigratorException::class);
    $this->expectExceptionMessageMatches('~^Could not write migration file "' . preg_quote($migrationsDir . '/' . Repository::MIGRATIONS_LEVEL_DB, '~') . '/Migration_\d{8}_\d{6}_Db.php"\.$~');
    $repository->create(Repository::MIGRATIONS_LEVEL_DB);
  }

  public function testItLoadsMigrationFiles(): void {
    $repository = $this->make(Repository::class, [
      'migrationsDir' => __DIR__ . '/TestMigrations',
    ]);
    $this->assertSame([
      [
        'level' => Repository::MIGRATIONS_LEVEL_DB,
        'name' => 'Migration_1',
      ],
      [
        'level' => Repository::MIGRATIONS_LEVEL_DB,
        'name' => 'Migration_2',
      ],
      [
        'level' => Repository::MIGRATIONS_LEVEL_DB,
        'name' => 'Migration_3',
      ],
      [
        'level' => Repository::MIGRATIONS_LEVEL_DB,
        'name' => 'Migration_4',
      ],
      [
        'level' => Repository::MIGRATIONS_LEVEL_APP,
        'name' => 'Migration_5',
      ],
    ], $repository->loadAll());
  }

  public function _after() {
    parent::_after();
    $this->removeDir(self::MIGRATIONS_OUTPUT_DIR);
  }

  private function checkItCreatesMigrationFiles(string $level): void {
    $migrations = $this->make(Repository::class, [
      'migrationsDir' => self::MIGRATIONS_OUTPUT_DIR,
      'templateFile' => self::TEMPLATE_FILE,
    ]);

    $migrations->create($level);

    $files = glob(self::MIGRATIONS_OUTPUT_DIR . '/' . $level . '/*.php') ?: [];
    $this->assertCount(1, $files);

    $templateFile = str_replace('{level}', $level, self::TEMPLATE_FILE);
    $migration = pathinfo($files[0], PATHINFO_FILENAME);
    $this->assertSame(
      str_replace("class {$level}MigrationTemplate", "class $migration", file_get_contents($templateFile) ?: ''),
      file_get_contents($files[0])
    );
  }

  private function removeDir(string $path): void {
    if (!file_exists($path) || !is_dir($path)) {
      return;
    }
    foreach (glob("$path/*") ?: [] as $file) {
      if (is_dir($file)) {
        $this->removeDir($file);
      } else {
        unlink($file);
      }
    }
    rmdir($path);
  }
}
