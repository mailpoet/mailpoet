<?php

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Migration extends SimpleWorker {
  const TASK_TYPE = 'migration';
  const BATCH_SIZE = 20;

  public function checkProcessingRequirements() {
    // if migration was completed, don't run it again
    $completedTasks = $this->getCompletedTasks();
    return empty($completedTasks);
  }

  public function prepareTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $unmigratedColumns = $this->checkUnmigratedColumnsExist();
    $unmigratedQueuesCount = 0;
    $unmigratedQueueSubscribers = [];

    if ($unmigratedColumns) {
      $unmigratedQueuesCount = $this->getUnmigratedQueues()->count();
      $unmigratedQueueSubscribers = $this->getTaskIdsForUnmigratedSubscribers();
    }

    if (!$unmigratedColumns ||
      ($unmigratedQueuesCount == 0
      && count($unmigratedQueueSubscribers) == 0)
    ) {
      // nothing to migrate, complete task
      $task->setProcessedAt(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp')));
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      $this->resumeSending();
      return false;
    }

    // pause sending while the migration is in process
    $this->pauseSending();
    return true;
  }

  public function pauseSending() {
    $mailerLog = MailerLog::getMailerLog();
    if (MailerLog::isSendingPaused($mailerLog)) {
      // sending is already paused
      return false;
    }
    $mailerLog = MailerLog::setError(
      $mailerLog,
      'migration',
      WPFunctions::get()->__('Your sending queue data is being migrated to allow better performance, sending is paused while the migration is in progress and will resume automatically upon completion. This may take a few minutes.')
    );
    return MailerLog::pauseSending($mailerLog);
  }

  public function resumeSending() {
    $mailerLog = MailerLog::getMailerLog();
    if (!MailerLog::isSendingPaused($mailerLog)) {
      // sending is not paused
      return false;
    }
    $error = MailerLog::getError($mailerLog);
    // only resume sending if it was paused by migration
    if (isset($error['operation']) && $error['operation'] === 'migration') {
      return MailerLog::resumeSending();
    }
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $this->migrateSendingQueues($timer);
    $this->migrateSubscribers($timer);
    $this->resumeSending();
    return true;
  }

  private function checkUnmigratedColumnsExist() {
    global $wpdb;
    $existingColumns = $wpdb->get_col('DESC ' . SendingQueueModel::$_table);
    return in_array('type', $existingColumns);
  }

  public function getUnmigratedQueues() {
    return SendingQueueModel::where('task_id', 0)
      ->whereNull('type');
  }

  public function getTaskIdsForUnmigratedSubscribers() {
    global $wpdb;
    $query = sprintf(
      'SELECT queues.`task_id` FROM %1$s queues INNER JOIN %2$s tasks ON queues.`task_id` = tasks.`id` ' .
      'WHERE tasks.`type` = "sending" AND (tasks.`status` IS NULL OR tasks.`status` = "paused") ' .
      'AND queues.`subscribers` != "" AND queues.`subscribers` != "N;"' .
      'AND queues.`count_total` > (SELECT COUNT(*) FROM %3$s subs WHERE subs.`task_id` = queues.`task_id`)',
      MP_SENDING_QUEUES_TABLE,
      MP_SCHEDULED_TASKS_TABLE,
      MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE
    );
    return $wpdb->get_col($query);
  }

  /*
   * Migrate all sending queues without converting subscriber data
   */
  public function migrateSendingQueues($timer) {
    global $wpdb;

    $queues = $this->getUnmigratedQueues()
      ->select('id')
      ->findArray();

    $columnList = [
      'status',
      'priority',
      'scheduled_at',
      'processed_at',
      'created_at',
      'updated_at',
      'deleted_at',
    ];

    if (!empty($queues)) {
      foreach (array_chunk($queues, self::BATCH_SIZE) as $queueBatch) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        foreach ($queueBatch as $queue) {
          // create a new scheduled task of type "sending"
          $wpdb->query(sprintf(
            'INSERT IGNORE INTO %1$s (`type`, %2$s) ' .
            'SELECT "sending", %2$s FROM %3$s WHERE `id` = %4$s',
            MP_SCHEDULED_TASKS_TABLE,
            '`' . join('`, `', $columnList) . '`',
            MP_SENDING_QUEUES_TABLE,
            $queue['id']
          ));
          // link the queue with the task via task_id
          $newTaskId = $wpdb->insert_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $wpdb->query(sprintf(
            'UPDATE %1$s SET `task_id` = %2$s WHERE `id` = %3$s',
            MP_SENDING_QUEUES_TABLE,
            $newTaskId,
            $queue['id']
          ));
        }
      }
    }

    return true;
  }

  /*
   * Migrate subscribers for in-progress sending tasks from the `subscribers` field to a separate table
   */
  public function migrateSubscribers($timer) {
    global $wpdb;

    // find in-progress queues that have serialized subscribers
    $taskIds = $this->getTaskIdsForUnmigratedSubscribers();

    // check if subscribers for each one were already migrated
    if (!empty($taskIds)) {
      $taskIds = $wpdb->get_col(sprintf(
        'SELECT queues.`task_id` FROM %1$s queues WHERE queues.`task_id` IN(' . join(',', array_map('intval', $taskIds)) . ') ' .
        'AND queues.`count_total` > (SELECT COUNT(*) FROM %2$s subs WHERE subs.`task_id` = queues.`task_id`)',
        MP_SENDING_QUEUES_TABLE,
        MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE
      ));
    }

    if (!empty($taskIds)) {
      foreach ($taskIds as $taskId) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        $this->migrateTaskSubscribers($taskId, $timer);
      }
    }

    return true;
  }

  public function migrateTaskSubscribers($taskId, $timer) {
    global $wpdb;

    $migratedUnprocessedCount = ScheduledTaskSubscriber::getUnprocessedCount($taskId);
    $migratedProcessedCount = ScheduledTaskSubscriber::getProcessedCount($taskId);

    $subscribers = $wpdb->get_var(sprintf(
      'SELECT `subscribers` FROM %1$s WHERE `task_id` = %2$d ' .
      'AND (`count_processed` > %3$d OR `count_to_process` > %4$d)',
      MP_SENDING_QUEUES_TABLE,
      $taskId,
      $migratedUnprocessedCount,
      $migratedProcessedCount
    ));

    // sanity check
    if (empty($subscribers)) {
      return false;
    }

    $subscribers = unserialize($subscribers);

    if (!empty($subscribers['to_process'])) {
      $subscribersToMigrate = array_slice($subscribers['to_process'], $migratedUnprocessedCount);
      foreach ($subscribersToMigrate as $subId) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        ScheduledTaskSubscriber::createOrUpdate([
          'task_id' => $taskId,
          'subscriber_id' => $subId,
          'processed' => ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        ]);
      }
    }

    if (!empty($subscribers['processed'])) {
      $subscribersToMigrate = array_slice($subscribers['processed'], $migratedProcessedCount);
      foreach ($subscribersToMigrate as $subId) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        ScheduledTaskSubscriber::createOrUpdate([
          'task_id' => $taskId,
          'subscriber_id' => $subId,
          'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
        ]);
      }
    }

    return true;
  }

  public function getNextRunDate($wp = null) {
    if (is_null($wp)) {
      $wp = new WPFunctions();
    }
    // run migration immediately
    return Carbon::createFromTimestamp($wp->currentTime('timestamp'));
  }
}
