<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Subscribers\ImportExport\Export\Export;

if (!defined('ABSPATH')) exit;

class ExportFilesCleanup extends SimpleWorker {
  const TASK_TYPE = 'export_files_cleanup';
  const DELETE_FILES_AFTER_X_DAYS = 1;

  function processTaskStrategy(ScheduledTask $task) {
    $iterator = new \GlobIterator(Export::getExportPath() . '/' . Export::getFilePrefix() . '*.*');
    foreach ($iterator as $file) {
      $name = $file->getPathname();
      $created = $file->getMTime();
      $now = new Carbon();
      if (Carbon::createFromTimestamp($created)->lessThan($now->subDays(self::DELETE_FILES_AFTER_X_DAYS))) {
        unlink($name);
      };
    }
    return true;
  }

}
