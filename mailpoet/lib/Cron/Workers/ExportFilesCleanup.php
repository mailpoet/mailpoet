<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\Config\Env;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoetVendor\Carbon\Carbon;

class ExportFilesCleanup extends SimpleWorker {
  const TASK_TYPE = 'export_files_cleanup';
  const DELETE_FILES_AFTER_X_DAYS = 1;
  const DELETE_STATS_FILES_AFTER_X_DAYS = 7;

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $this->cleanup(
      Export::getExportPath() . '/' . Export::getFilePrefix() . '*.*',
      self::DELETE_FILES_AFTER_X_DAYS
    );
    $this->cleanup(
      Env::$tempPath . '/' . Export::getFilePrefix() . '*.*',
      self::DELETE_FILES_AFTER_X_DAYS
    );
    $this->cleanup(
      ExportDownload::getExportDirectory() . '/' . StatisticsExporter::FILE_PREFIX . '*.*',
      self::DELETE_STATS_FILES_AFTER_X_DAYS
    );
    $this->cleanup(
      Env::$tempPath . '/' . StatisticsExporter::FILE_PREFIX . '*.*',
      self::DELETE_STATS_FILES_AFTER_X_DAYS
    );
    return true;
  }

  private function cleanup(string $globPattern, int $deleteAfterDays): void {
    $iterator = new \GlobIterator($globPattern);
    foreach ($iterator as $file) {
      if (is_string($file)) {
        continue;
      }
      $name = $file->getPathname();
      $created = $file->getMTime();
      $now = new Carbon();
      if (Carbon::createFromTimestamp((int)$created)->lessThan($now->subDays($deleteAfterDays))) {
        unlink($name);
      }
    }
  }
}
