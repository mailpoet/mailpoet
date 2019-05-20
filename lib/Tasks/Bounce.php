<?php
namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;

if (!defined('ABSPATH')) exit;

class Bounce {
  static function prepareSubscribers(ScheduledTask $task) {
    // Prepare subscribers on the DB side for performance reasons
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT ? as task_id, s.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBERS_TABLE . ' s
       WHERE s.`deleted_at` IS NULL
       AND s.`status` IN (?, ?)',
      [
        $task->id,
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED,
      ]
    );
  }
}
