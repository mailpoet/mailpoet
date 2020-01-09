<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class UnsubscribeTokens extends SimpleWorker {
  const TASK_TYPE = 'unsubscribe_tokens';
  const BATCH_SIZE = 1000;
  const AUTOMATIC_SCHEDULING = false;

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $meta = $task->getMeta();
    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $subscribersCount = $this->addTokens(Subscriber::class, $meta['last_subscriber_id']);
      $task->meta = $meta;
      $task->save();
    } while ($subscribersCount === self::BATCH_SIZE);
    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $newslettersCount = $this->addTokens(Newsletter::class, $meta['last_newsletter_id']);
      $task->meta = $meta;
      $task->save();
    } while ($newslettersCount === self::BATCH_SIZE);
    if ($subscribersCount > 0 || $newslettersCount > 0) {
      return false;
    }
    return true;
  }

  private function addTokens($model, &$lastProcessedId = 0) {
    $instances = $model::whereNull('unsubscribe_token')
      ->whereGt('id', (int)$lastProcessedId)
      ->orderByAsc('id')
      ->limit(self::BATCH_SIZE)
      ->findMany();
    foreach ($instances as $instance) {
      $lastProcessedId = $instance->id;
      $instance->set('unsubscribe_token', Security::generateUnsubscribeToken($model));
      $instance->save();
    }
    return count($instances);
  }

  public function getNextRunDate() {
    return Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
  }
}
