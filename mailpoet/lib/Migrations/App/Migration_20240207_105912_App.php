<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Migrator\AppMigration;
use MailPoetVendor\Doctrine\DBAL\Connection;

/**
 * We've had a set of bugs where campaign type newsletters (see NewsletterEntity::CAMPAIGN_TYPES),
 * such as post notifications, were getting stuck in the following state:
 * - The newsletter was in the "sending" state.
 * - The task failed to complete and ended up in the "invalid" state.
 *
 * This migration completes tasks that sent out all emails
 * and pauses those that have unprocessed subscribers.
 */
class Migration_20240207_105912_App extends AppMigration {
  public function run(): void {
    $this->pauseInvalidTasksWithUnprocessedSubscribers();
    $this->completeInvalidTasksWithAllSubscribersProcessed();
  }

  private function pauseInvalidTasksWithUnprocessedSubscribers(): void {
    $ids = $this->entityManager->createQueryBuilder()
      ->select('DISTINCT t.id')
      ->from(ScheduledTaskEntity::class, 't')
      ->join('t.subscribers', 's', 'WITH', 's.processed = :unprocessed')
      ->join('t.sendingQueue', 'q')
      ->join('q.newsletter', 'n')
      ->where('t.deletedAt IS NULL')
      ->andWhere('t.status = :invalid')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.status = :sending')
      ->andWhere('n.type IN (:campaignTypes)')
      ->setParameter('unprocessed', ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED)
      ->setParameter('invalid', ScheduledTaskEntity::STATUS_INVALID)
      ->setParameter('sending', NewsletterEntity::STATUS_SENDING)
      ->setParameter('campaignTypes', NewsletterEntity::CAMPAIGN_TYPES)
      ->getQuery()
      ->getSingleColumnResult();

    $this->entityManager->createQueryBuilder()
      ->update(ScheduledTaskEntity::class, 't')
      ->set('t.status', ':paused')
      ->where('t.id IN (:ids)')
      ->setParameter('paused', ScheduledTaskEntity::STATUS_PAUSED)
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();
  }

  private function completeInvalidTasksWithAllSubscribersProcessed(): void {
    $ids = $this->entityManager->createQueryBuilder()
      ->select('DISTINCT t.id, n.id AS nid, t.updatedAt')
      ->from(ScheduledTaskEntity::class, 't')
      ->leftJoin('t.subscribers', 's', 'WITH', 's.processed = :unprocessed')
      ->join('t.sendingQueue', 'q')
      ->join('q.newsletter', 'n')
      ->where('t.deletedAt IS NULL')
      ->andWhere('t.status = :invalid')
      ->andWhere('s.task IS NULL')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.status = :sending')
      ->andWhere('n.type IN (:campaignTypes)')
      ->setParameter('unprocessed', ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED)
      ->setParameter('invalid', ScheduledTaskEntity::STATUS_INVALID)
      ->setParameter('sending', NewsletterEntity::STATUS_SENDING)
      ->setParameter('campaignTypes', NewsletterEntity::CAMPAIGN_TYPES)
      ->getQuery()
      ->getSingleColumnResult();

    // update sending queue counts
    $this->entityManager->createQueryBuilder()
      ->update(SendingQueueEntity::class, 'q')
      ->set('q.countProcessed', 'q.countTotal')
      ->set('q.countToProcess', 0)
      ->where('q.task IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // complete the invalid tasks
    $this->entityManager->createQueryBuilder()
      ->update(ScheduledTaskEntity::class, 't')
      ->set('t.status', ':completed')
      ->where('t.id IN (:ids)')
      ->setParameter('completed', ScheduledTaskEntity::STATUS_COMPLETED)
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // mark newsletters as sent, update "sentAt" (DBAL needed to be able to use JOIN)
    $newslettersTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $sendingQueuesTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "
        UPDATE $newslettersTable n
        JOIN $sendingQueuesTable q ON n.id = q.newsletter_id
        JOIN $scheduledTasksTable t ON q.task_id = t.id
        SET
          n.status = :sent,
          n.sent_at = t.updated_at
        WHERE t.id IN (:ids)
      ",
      ['sent' => NewsletterEntity::STATUS_SENT, 'ids' => $ids],
      ['ids' => Connection::PARAM_INT_ARRAY]
    );
  }
}
