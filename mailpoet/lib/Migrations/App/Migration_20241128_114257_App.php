<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\NewslettersRepository;

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
      $this->unpauseTasks($newsletter);
      $this->entityManager->flush();
    }
  }

  private function unpauseTasks(NewsletterEntity $newsletter): void {
    $queues = $newsletter->getUnfinishedQueues();
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      if ($task && $task->getStatus() === ScheduledTaskEntity::STATUS_PAUSED) {
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
      }
    }
  }
}
