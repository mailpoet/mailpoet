<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tasks;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;

class Bounce {
  public static function prepareSubscribers(ScheduledTaskEntity $task) {
    // Prepare subscribers on the DB side for performance reasons
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT ? as task_id, s.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBERS_TABLE . ' s
       WHERE s.`deleted_at` IS NULL
       AND s.`status` IN (?, ?)',
      [
        $task->getId(),
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED,
      ]
    );
  }
}
