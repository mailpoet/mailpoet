<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportFilesCleanup extends SimpleWorker {
  const TASK_TYPE = 'export_files_cleanup';
  const DELETE_FILES_AFTER_X_DAYS = 1;

  function processTaskStrategy(ScheduledTask $task, $timer) {
    $iterator = new \GlobIterator(Export::getExportPath() . '/' . Export::getFilePrefix() . '*.*');
    foreach ($iterator as $file) {
      if (is_string($file)) {
        continue;
      }
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
