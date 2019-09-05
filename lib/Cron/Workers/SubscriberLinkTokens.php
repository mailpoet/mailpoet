<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\Subscriber;
use MailPoet\Models\ScheduledTask;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class SubscriberLinkTokens extends SimpleWorker {
  const TASK_TYPE = 'subscriber_link_tokens';
  const BATCH_SIZE = 10000;
  const AUTOMATIC_SCHEDULING = false;

  function processTaskStrategy(ScheduledTask $task) {
    $count = Subscriber::whereNull('link_token')->count();
    if ($count) {
      $auth_key = defined('AUTH_KEY') ? AUTH_KEY : '';
      \ORM::rawExecute(sprintf('UPDATE %s SET link_token = MD5(CONCAT(?, email)) WHERE link_token IS NULL LIMIT ?', Subscriber::$_table), [$auth_key, self::BATCH_SIZE]);
      self::schedule();
    }
    return true;
  }

  static function getNextRunDate() {
    $wp = new WPFunctions();
    return Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }
}
