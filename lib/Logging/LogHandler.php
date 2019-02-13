<?php

namespace MailPoet\Logging;

use Carbon\Carbon;
use MailPoetVendor\Monolog\Handler\AbstractProcessingHandler;
use MailPoet\Models\Log;

class LogHandler extends AbstractProcessingHandler {

  /**
   * Percentage value, what is the probability of running purge routine
   * @var int
   */
  const LOG_PURGE_PROBABILITY = 5;

  /**
   * Logs older than this many days will be deleted
   */
  const DAYS_TO_KEEP_LOGS = 30;

  protected function write(array $record) {
    $model = $this->createNewLogModel();
    $model->hydrate([
      'name' => $record['channel'],
      'level' => $record['level'],
      'message' => $record['formatted'],
      'created_at' => $record['datetime']->format('Y-m-d H:i:s'),
    ]);
    $model->save();

    if ($this->getRandom() <= self::LOG_PURGE_PROBABILITY) {
      $this->purgeOldLogs();
    }
  }

  private function createNewLogModel() {
    return Log::create();
  }

  private function getRandom() {
    return rand(0, 100);
  }

  private function purgeOldLogs() {
    Log::whereLt('created_at', Carbon::create()->subDays(self::DAYS_TO_KEEP_LOGS)->toDateTimeString())
       ->deleteMany();
  }

}
