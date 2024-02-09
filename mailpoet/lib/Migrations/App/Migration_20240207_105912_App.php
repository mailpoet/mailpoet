<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Migrator\AppMigration;

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
    $result = $this->entityManager->createQueryBuilder()
      ->select('DISTINCT t.id, n.id AS nid')
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
      ->getResult();

    $ids = array_column($result, 'id');
    $newsletterIds = array_column($result, 'nid');

    $this->entityManager->createQueryBuilder()
      ->update(ScheduledTaskEntity::class, 't')
      ->set('t.status', ':completed')
      ->where('t.id IN (:ids)')
      ->setParameter('completed', ScheduledTaskEntity::STATUS_COMPLETED)
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    $this->entityManager->createQueryBuilder()
      ->update(NewsletterEntity::class, 'n')
      ->set('n.status', ':sent')
      ->where('n.deletedAt IS NULL')
      ->andWhere('n.id IN (:newsletterIds)')
      ->setParameter('sent', NewsletterEntity::STATUS_SENT)
      ->setParameter('newsletterIds', $newsletterIds)
      ->getQuery()
      ->execute();
  }
}
