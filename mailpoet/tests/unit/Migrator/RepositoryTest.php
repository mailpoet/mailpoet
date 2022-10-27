<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoetUnitTest;

class RepositoryTest extends MailPoetUnitTest {
  private const MIGRATIONS_OUTPUT_DIR = __DIR__ . '/../../_output/migrations-tests';
  private const TEMPLATE_FILE = __DIR__ . '/../../../lib/Migrator/MigrationTemplate.php';

  protected function _before() {
    parent::_before();
    $this->removeDir(self::MIGRATIONS_OUTPUT_DIR);
    mkdir(self::MIGRATIONS_OUTPUT_DIR, 0777, true);
  }

  public function testItCreatesMigrationFile(): void {
    $migrations = $this->make(Repository::class, [
      'migrationsDir' => self::MIGRATIONS_OUTPUT_DIR,
      'templateFile' => self::TEMPLATE_FILE,
    ]);

    $migrations->create();

    $files = glob(self::MIGRATIONS_OUTPUT_DIR . '/*.php') ?: [];
    $this->assertCount(1, $files);

    $migration = pathinfo($files[0], PATHINFO_FILENAME);
    $this->assertSame(
      str_replace("class MigrationTemplate", "class $migration", file_get_contents(self::TEMPLATE_FILE) ?: ''),
      file_get_contents($files[0])
    );
  }

  public function testItFailsCreatingWithInvalidTemplateFile(): void {
    $templateFile = __DIR__ . '/NonExistentTemplateFile.php';
    $repository = $this->make(Repository::class, [
      'templateFile' => $templateFile,
    ]);

    $this->expectException(MigratorException::class);
    $this->expectExceptionMessage(sprintf('Could not read migration template file "%s"', $templateFile));
    $repository->create();
  }

  public function testItFailsCreatingWhenCantSave(): void {
    $migrationsDir = __DIR__ . '/Non/Existent/Directory';
    $repository = $this->make(Repository::class, [
      'migrationsDir' => $migrationsDir,
      'templateFile' => self::TEMPLATE_FILE,
    ]);

    $this->expectException(MigratorException::class);
    $this->expectExceptionMessageMatches('~^Could not write migration file "' . preg_quote($migrationsDir, '~') . '/Migration_\d{8}_\d{6}.php"\.$~');
    $repository->create();
  }

  public function _after() {
    parent::_after();
    $this->removeDir(self::MIGRATIONS_OUTPUT_DIR);
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
