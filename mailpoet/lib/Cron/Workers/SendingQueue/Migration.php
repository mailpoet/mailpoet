<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Migration extends SimpleWorker {
  const TASK_TYPE = 'migration';
  const BATCH_SIZE = 20;

  /** @var EntityManager */
  private $entityManager;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    parent::__construct();
    $this->entityManager = $entityManager;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
  }

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
      $unmigratedQueuesCount = count($this->getUnmigratedQueueIds());
      $unmigratedQueueSubscribers = $this->getTaskIdsForUnmigratedSubscribers();
    }

    if (
      !$unmigratedColumns ||
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
      __('Your sending queue data is being migrated to allow better performance, sending is paused while the migration is in progress and will resume automatically upon completion. This may take a few minutes.', 'mailpoet')
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
    $existingColumns = $wpdb->get_col('DESC ' . esc_sql($this->getTableName(SendingQueueEntity::class)));
    return in_array('type', $existingColumns);
  }

  /**
   * @return array
   */
  public function getUnmigratedQueueIds(): array {
    $sendingQueuesTable = $this->getTableName(SendingQueueEntity::class);
    return $this->entityManager->getConnection()->executeQuery("
      SELECT id
      FROM {$sendingQueuesTable}
      WHERE task_id = 0
        AND type IS NULL
    ")->fetchFirstColumn();
  }

  public function getTaskIdsForUnmigratedSubscribers() {
    global $wpdb;
    $query = sprintf(
      'SELECT queues.`task_id` FROM %1$s queues INNER JOIN %2$s tasks ON queues.`task_id` = tasks.`id` ' .
      'WHERE tasks.`type` = \'sending\' AND (tasks.`status` IS NULL OR tasks.`status` = \'paused\') ' .
      'AND queues.`subscribers` != \'\' AND queues.`subscribers` != \'N;\'' .
      'AND queues.`count_total` > (SELECT COUNT(*) FROM %3$s subs WHERE subs.`task_id` = queues.`task_id`)',
      esc_sql($this->getTableName(SendingQueueEntity::class)),
      esc_sql($this->getTableName(ScheduledTaskEntity::class)),
      esc_sql($this->getTableName(ScheduledTaskSubscriberEntity::class))
    );
    return $wpdb->get_col($query);
  }

  /*
   * Migrate all sending queues without converting subscriber data
   */
  public function migrateSendingQueues($timer) {
    global $wpdb;

    $queueIds = $this->getUnmigratedQueueIds();

    $columnList = [
      'status',
      'priority',
      'scheduled_at',
      'processed_at',
      'created_at',
      'updated_at',
      'deleted_at',
    ];

    if (!empty($queueIds)) {
      foreach (array_chunk($queueIds, self::BATCH_SIZE) as $queueBatch) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        foreach ($queueBatch as $queueId) {
          // create a new scheduled task of type "sending"

          // Constants are safe, queue ID is cast to int.
          // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
          $wpdb->query(sprintf(
            'INSERT IGNORE INTO %1$s (`type`, %2$s) ' .
            'SELECT \'sending\', %2$s FROM %3$s WHERE `id` = %4$s',
            $this->getTableName(ScheduledTaskEntity::class),
            '`' . join('`, `', $columnList) . '`',
            $this->getTableName(SendingQueueEntity::class),
            $queueId
          ));

          // link the queue with the task via task_id
          $newTaskId = $wpdb->insert_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $table = esc_sql($this->getTableName(SendingQueueEntity::class));
          $query = $wpdb->prepare(
            "UPDATE `$table` SET `task_id` = %s WHERE `id` = %s",
            $newTaskId,
            $queueId
          );
          $wpdb->query($query);
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
        esc_sql($this->getTableName(SendingQueueEntity::class)),
        esc_sql($this->getTableName(ScheduledTaskSubscriberEntity::class))
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

    $migratedUnprocessedCount = $this->scheduledTaskSubscribersRepository->countBy([
      'task' => $taskId,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
    ]);
    $migratedProcessedCount = $this->scheduledTaskSubscribersRepository->countBy([
      'task' => $taskId,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
    ]);

    $table = $this->getTableName(SendingQueueEntity::class);
    // All parameters are safe
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    $subscribers = $wpdb->get_var($wpdb->prepare(
      "SELECT `subscribers` FROM `$table` WHERE `task_id` = %d AND (`count_processed` > %d OR `count_to_process` > %d)",
      (int)$taskId,
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

        $this->scheduledTaskSubscribersRepository->createOrUpdate([
          'task_id' => $taskId,
          'subscriber_id' => $subId,
          'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
        ]);
      }
    }

    if (!empty($subscribers['processed'])) {
      $subscribersToMigrate = array_slice($subscribers['processed'], $migratedProcessedCount);
      foreach ($subscribersToMigrate as $subId) {
        // abort if execution limit is reached
        $this->cronHelper->enforceExecutionLimit($timer);

        $this->scheduledTaskSubscribersRepository->createOrUpdate([
          'task_id' => $taskId,
          'subscriber_id' => $subId,
          'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
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

  private function getTableName(string $entityName): string {
    return $this->entityManager->getClassMetadata($entityName)->getTableName();
  }
}
