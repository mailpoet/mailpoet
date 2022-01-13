<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

if (!defined('ABSPATH')) exit;

class SubscriberLinkTokens extends SimpleWorker {
  const TASK_TYPE = 'subscriber_link_tokens';
  const BATCH_SIZE = 10000;
  const AUTOMATIC_SCHEDULING = false;

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $count = Subscriber::whereNull('link_token')->count();
    if ($count) {
      $authKey = defined('AUTH_KEY') ? AUTH_KEY : '';
      ORM::rawExecute(
        sprintf('UPDATE %s SET link_token = SUBSTRING(MD5(CONCAT(?, email)), 1, ?) WHERE link_token IS NULL LIMIT ?', Subscriber::$_table),
        [$authKey, Subscriber::OBSOLETE_LINK_TOKEN_LENGTH, self::BATCH_SIZE]
      );
      $this->schedule();
    }
    return true;
  }

  public function getNextRunDate() {
    $wp = new WPFunctions();
    return Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }
}
