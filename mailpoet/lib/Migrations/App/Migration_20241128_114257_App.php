<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoetVendor\Carbon\Carbon;

/**
 * Some newsletters might have an incorrect status due to a bug where we set the status 'sending'
 * to automation emails.
 *
 * See https://mailpoet.atlassian.net/browse/MAILPOET-6241
 */
class Migration_20241128_114257_App extends AppMigration {
  public function run(): void {
    $newsletterRepository = $this->container->get(NewslettersRepository::class);
    $newsletters = $newsletterRepository->findBy([
      'type' => NewsletterEntity::ACTIVABLE_EMAILS,
      'status' => NewsletterEntity::STATUS_SENDING,
    ]);

    foreach ($newsletters as $newsletter) {
      $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
      // As a consequence of the bug, some tasks might be paused, we need to unpause them
      $this->updateTasks($newsletter);
    }
  }

  private function updateTasks(NewsletterEntity $newsletter): void {
    $oldTaskThreshold = (new Carbon())->subDays(30);
    $queues = $newsletter->getUnfinishedQueues();
    foreach ($queues as $queue) {
      $task = $queue->getTask();

      // Switch relatively new paused tasks to scheduled
      if ($task && ($task->getScheduledAt() > $oldTaskThreshold) && $task->getStatus() === ScheduledTaskEntity::STATUS_PAUSED) {
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $this->entityManager->flush();
        continue;
      }

      // Switch old paused tasks to completed and mark scheduled task subscribers as failed
      // This will prevent sending outdated automatic emails. When marked as failed, the user still can resend them in Sending Status screen.
      if ($task && ($task->getScheduledAt() <= $oldTaskThreshold) && $task->getStatus() === ScheduledTaskEntity::STATUS_PAUSED) {
        $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
        $task->setProcessedAt(new Carbon());
        $this->entityManager->flush();
        $scheduledTaskSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
        $this->entityManager->getConnection()->executeQuery(
          "UPDATE $scheduledTaskSubscribersTable
          SET `processed` = :processed, `failed` = :failed, `error` = :error
          WHERE task_id = :task_id",
          [
            'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
            'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED,
            'error' => 'Sending timed out for being paused too long.',
            'task_id' => $task->getId(),
          ]
        );
        $this->entityManager->refresh($task);
      }
    }
    $this->entityManager->flush();
  }
}
