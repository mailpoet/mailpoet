<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class DataInconsistencyRepository {
  private EntityManager $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function getOrphanedSendingTasksCount(): int {
    $builder = $this->entityManager->createQueryBuilder()
      ->select('count(st.id)');
    return (int)$this->buildOrphanedSendingTasksQuery($builder)->getSingleScalarResult();
  }

  public function getOrphanedScheduledTasksSubscribersCount(): int {
    $stTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(*) FROM $stsTable sts
      LEFT JOIN $stTable st ON st.`id` = sts.`task_id`
      WHERE st.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function getSendingQueuesWithoutNewsletterCount(): int {
    $sqTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(*) FROM $sqTable sq
      LEFT JOIN $newsletterTable n ON n.`id` = sq.`newsletter_id`
      WHERE n.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function getOrphanedSubscriptionsCount(): int {
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $segmentTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(distinct ss.`id`) FROM $subscriberSegmentTable ss
      LEFT JOIN $segmentTable seg ON seg.`id` = ss.`segment_id`
      LEFT JOIN $subscriberTable sub ON sub.`id` = ss.`subscriber_id`
      WHERE seg.`id` IS NULL OR sub.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function cleanupOrphanedSendingTasks(): int {
    $ids = $this->buildOrphanedSendingTasksQuery(
      $this->entityManager->createQueryBuilder()
      ->select('st.id')
    )->getResult();

    if (!$ids) {
      return 0;
    }

    // delete the orphaned tasks
    $qb = $this->entityManager->createQueryBuilder();
    $countDeletedTasks = $qb->delete(ScheduledTaskEntity::class, 'st')
      ->where($qb->expr()->in('st.id', ':ids'))
      ->setParameter('ids', array_column($ids, 'id'))
      ->getQuery()
      ->execute();

    // delete the scheduled tasks subscribers
    $qb = $this->entityManager->createQueryBuilder();
    $qb->delete(ScheduledTaskSubscriberEntity::class, 'sts')
      ->where($qb->expr()->in('sts.task', ':ids'))
      ->setParameter('ids', array_column($ids, 'id'))
      ->getQuery()
      ->execute();

    return $countDeletedTasks;
  }

  public function cleanupOrphanedScheduledTaskSubscribers(): int {
    $stTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    return (int)$this->entityManager->getConnection()->executeStatement("
       DELETE sts FROM $stsTable sts
       LEFT JOIN $stTable st ON st.`id` = sts.`task_id`
       WHERE st.`id` IS NULL
    ");
  }

  public function cleanupSendingQueuesWithoutNewsletter(): int {
    $sqTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $deletedQueuesCount = (int)$this->entityManager->getConnection()->executeStatement("
      DELETE sq FROM $sqTable sq
      LEFT JOIN $newsletterTable n ON n.`id` = sq.`newsletter_id`
      WHERE n.`id` IS NULL
    ");

    $this->cleanupOrphanedSendingTasks();
    return $deletedQueuesCount;
  }

  public function cleanupOrphanedSubscriptions(): int {
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $segmentTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    return (int)$this->entityManager->getConnection()->executeStatement("
      DELETE ss FROM $subscriberSegmentTable ss
      LEFT JOIN $segmentTable seg ON seg.`id` = ss.`segment_id`
      LEFT JOIN $subscriberTable sub ON sub.`id` = ss.`subscriber_id`
      WHERE seg.`id` IS NULL OR sub.`id` IS NULL
    ");
  }

  private function buildOrphanedSendingTasksQuery(QueryBuilder $queryBuilder): Query {
    return $queryBuilder
      ->from(ScheduledTaskEntity::class, 'st')
      ->leftJoin('st.sendingQueue', 'sq')
      ->where('sq.id IS NULL')
      ->andWhere('st.type = :type')
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->getQuery();
  }
}
