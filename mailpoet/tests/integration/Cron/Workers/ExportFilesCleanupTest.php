<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Config\Env;
use MailPoet\Cron\Workers\ExportFilesCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportFilesCleanupTest extends \MailPoetTest {
  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();

    $this->previousTempPath = (string)Env::$tempPath;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-export-cleanup-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
  }

  public function _after() {
    $this->cleanupDirectory($this->tempDir);
    if (is_dir($this->tempDir)) {
      rmdir($this->tempDir);
    }
    Env::$tempPath = $this->previousTempPath;

    parent::_after();
  }

  public function testItCleansUpOldSubscriberExportFiles() {
    $oldFilePath = $this->tempDir . '/' . Export::getFilePrefix() . 'old_file.csv';
    $newFilePath = $this->tempDir . '/' . Export::getFilePrefix() . 'new_file.csv';
    touch($oldFilePath, time() - (60 * 60 * 24 * 2));
    touch($newFilePath);

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->assertFileExists($newFilePath);
    $this->assertFileNotExists($oldFilePath);
  }

  public function testItCleansUpOldSubscriberExportFilesFromProtectedDirectory() {
    if (!is_dir(ExportDownload::getExportDirectory())) {
      mkdir(ExportDownload::getExportDirectory(), 0777, true);
    }
    $oldFilePath = ExportDownload::getExportDirectory() . '/' . Export::getFilePrefix() . 'old_file.csv';
    $newFilePath = ExportDownload::getExportDirectory() . '/' . Export::getFilePrefix() . 'new_file.csv';
    touch($oldFilePath, time() - (60 * 60 * 24 * 2));
    touch($newFilePath);

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->assertFileExists($newFilePath);
    $this->assertFileNotExists($oldFilePath);
  }

  public function testItCleansUpOldStatisticsExportFiles() {
    $oldFilePath = $this->tempDir . '/' . StatisticsExporter::FILE_PREFIX . 'old_file.csv';
    $newFilePath = $this->tempDir . '/' . StatisticsExporter::FILE_PREFIX . 'new_file.csv';
    touch($oldFilePath, time() - (60 * 60 * 24 * 8));
    touch($newFilePath, time() - (60 * 60 * 24 * 6));

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->assertFileExists($newFilePath);
    $this->assertFileNotExists($oldFilePath);
  }

  public function testItCleansUpOldStatisticsExportFilesFromProtectedDirectory() {
    if (!is_dir(ExportDownload::getExportDirectory())) {
      mkdir(ExportDownload::getExportDirectory(), 0777, true);
    }
    $oldFilePath = ExportDownload::getExportDirectory() . '/' . StatisticsExporter::FILE_PREFIX . 'old_file.csv';
    $newFilePath = ExportDownload::getExportDirectory() . '/' . StatisticsExporter::FILE_PREFIX . 'new_file.csv';
    touch($oldFilePath, time() - (60 * 60 * 24 * 8));
    touch($newFilePath, time() - (60 * 60 * 24 * 6));

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->assertFileExists($newFilePath);
    $this->assertFileNotExists($oldFilePath);
  }

  private function cleanupDirectory(string $directory): void {
    foreach (glob($directory . '/*') ?: [] as $file) {
      if (is_dir($file)) {
        $this->cleanupDirectory($file);
        rmdir($file);
        continue;
      }
      unlink($file);
    }
  }
}
