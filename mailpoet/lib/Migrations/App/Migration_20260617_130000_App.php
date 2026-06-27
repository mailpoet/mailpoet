<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\Workers\BulkConfirmationEmailResend;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\MigrationSendingPauser;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;

/**
 * Backfills the new queue table for in-flight sending and confirmation-resend tasks.
 *
 * Sending and bulk confirmation resend now read pending recipients from
 * `scheduled_task_queued_subscribers` instead of `scheduled_task_subscribers`.
 * Existing not-yet-completed tasks of those types still have their pending
 * (`processed = 0`) rows in the old table, so we copy those into the queue and
 * delete them from the log (copy-then-delete keeps a uniform model: pending only
 * in the queue, processed only in the log).
 *
 * Scoped to not-completed tasks of those types (found via the smaller, better
 * indexed scheduled_tasks table) so the copy is a PK-range per task, never a full
 * scan of the log.
 *
 * Sending is paused for the duration via MigrationSendingPauser. resume() is
 * only reached on success: if this migration is interrupted it leaves sending
 * paused (with the user notice) and is retried on the next run. The move runs in
 * idempotent batches (see ScheduledTaskSubscriberMover::backfillPendingToQueue),
 * so each retry only processes the rows still left in the log — the migration
 * converges even on a host where a single unbounded statement would time out.
 */
class Migration_20260617_130000_App extends AppMigration {
  public function run(): void {
    $connection = $this->entityManager->getConnection();
    $tasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();

    $taskIds = $connection->executeQuery(
      "SELECT `id` FROM {$tasksTable}
       WHERE `type` IN (:types)
         AND (`status` IS NULL OR `status` != :completed)
         AND `deleted_at` IS NULL",
      [
        'types' => [SendingQueueWorker::TASK_TYPE, BulkConfirmationEmailResend::TASK_TYPE],
        'completed' => ScheduledTaskEntity::STATUS_COMPLETED,
      ],
      [
        'types' => ArrayParameterType::STRING,
        'completed' => ParameterType::STRING,
      ]
    )->fetchFirstColumn();

    /** @var MigrationSendingPauser $pauser */
    $pauser = $this->container->get(MigrationSendingPauser::class);
    if (!$taskIds) {
      $pauser->resume();
      return;
    }

    $pauser->pause();

    /** @var ScheduledTaskSubscriberMover $mover */
    $mover = $this->container->get(ScheduledTaskSubscriberMover::class);
    $mover->backfillPendingToQueue(array_map(static fn($id): int => is_scalar($id) ? (int)$id : 0, $taskIds));

    $pauser->resume();
  }
}
