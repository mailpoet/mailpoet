<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Subscriber;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class SubscriberLinkTokens extends SimpleWorker {
  const TASK_TYPE = 'subscriber_link_tokens';
  const BATCH_SIZE = 1000;
  const AUTOMATIC_SCHEDULING = false;

  function processTaskStrategy(ScheduledTask $task) {
    $count = $this->addTokens();
    while ($count === self::BATCH_SIZE) {
      CronHelper::enforceExecutionLimit($this->timer);
      $count = $this->addTokens();
    };
    if ($count > 0) {
      self::schedule();
    }
    return true;
  }

  private function addTokens() {
    $instances = Subscriber::whereNull('link_token')->limit(self::BATCH_SIZE)->findMany();
    foreach ($instances as $instance) {
      $instance->set('link_token', Subscriber::generateToken($instance->email));
      $instance->save();
    }
    return count($instances);
  }

  static function getNextRunDate() {
    $wp = new WPFunctions();
    return Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }
}
