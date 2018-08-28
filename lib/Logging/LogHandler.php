<?php

namespace MailPoet\Logging;

use Carbon\Carbon;
use MailPoet\Dependencies\Monolog\Handler\AbstractProcessingHandler;
use MailPoet\Models\Log;

class LogHandler extends AbstractProcessingHandler {

  /**
   * Percentage value, what is the probability of running purge routine
   * @var int
   */
  CONST LOG_PURGE_PROBABILITY = 5;

  /**
   * Logs older than this many days will be deleted
   */
  CONST DAYS_TO_KEEP_LOGS = 30;

  protected function write(array $record) {
    $model = Log::create();
    $model->hydrate([
      'name' => $record['channel'],
      'level' => $record['level'],
      'message' => $record['formatted'],
      'created_at' => $record['datetime']->format('Y-m-d H:i:s'),
    ]);
    $model->save();

    if(rand(0, 100) <= self::LOG_PURGE_PROBABILITY) {
      $this->purgeOldLogs();
    }
  }

  private function purgeOldLogs() {
    Log::whereLt('created_at', Carbon::create()->subDays(self::DAYS_TO_KEEP_LOGS)->toDateTimeString())
       ->deleteMany();
  }

}