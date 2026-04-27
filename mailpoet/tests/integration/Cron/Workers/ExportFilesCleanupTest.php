<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Config\Env;
use MailPoet\Cron\Workers\ExportFilesCleanup;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
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
    foreach (glob($this->tempDir . '/*') ?: [] as $file) {
      unlink($file);
    }
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
}
