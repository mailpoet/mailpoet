<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class DataInconsistencyRepository {
  const DELETE_ROWS_LIMIT = 10000;

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
    $this->createOrphanedScheduledTaskSubscribersTemporaryTables();
    $count = $this->getOrphanedScheduledTasksSubscribersCountFromTemporaryTables();
    $this->dropOrphanedScheduledTaskSubscribersTemporaryTables();
    return $count;
  }

  private function getOrphanedScheduledTasksSubscribersCountFromTemporaryTables(): int {
    $connection = $this->entityManager->getConnection();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    /** @var string $count */
    $count = $connection->executeQuery("
      SELECT COUNT(*) FROM $stsTable sts WHERE sts.task_id IN (SELECT task_id FROM orphaned_task_ids)
    ")->fetchOne();
    return intval($count);
  }

  public function getSendingQueuesWithoutNewsletterCount(): int {
    $sqTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    /** @var string $count */
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
    /** @var string $count */
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(distinct ss.`id`) FROM $subscriberSegmentTable ss
      LEFT JOIN $segmentTable seg ON seg.`id` = ss.`segment_id`
      LEFT JOIN $subscriberTable sub ON sub.`id` = ss.`subscriber_id`
      WHERE seg.`id` IS NULL OR sub.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function getOrphanedNewsletterLinksCount(): int {
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterLinkTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
    /** @var string $count */
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(distinct nl.`id`) FROM $newsletterLinkTable nl
      LEFT JOIN $newsletterTable n ON n.`id` = nl.`newsletter_id`
      LEFT JOIN $sendingQueueTable sq ON sq.`id` = nl.`queue_id`
      WHERE n.`id` IS NULL OR sq.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function getOrphanedNewsletterPostsCount(): int {
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $newsletterPostTable = $this->entityManager->getClassMetadata(NewsletterPostEntity::class)->getTableName();
    /** @var string $count */
    $count = $this->entityManager->getConnection()->executeQuery("
      SELECT count(distinct np.`id`) FROM $newsletterPostTable np
      LEFT JOIN $newsletterTable n ON n.`id` = np.`newsletter_id`
      WHERE n.`id` IS NULL
    ")->fetchOne();
    return intval($count);
  }

  public function cleanupOrphanedSendingTasks(): int {
    /** @var array<int, array{id: string}> $ids */
    $ids = $this->buildOrphanedSendingTasksQuery(
      $this->entityManager->createQueryBuilder()
      ->select('st.id')
    )->getResult();

    if (!$ids) {
      return 0;
    }
    $ids = array_column($ids, 'id');
    // delete the orphaned tasks
    $qb = $this->entityManager->createQueryBuilder();
    $countDeletedTasks = $qb->delete(ScheduledTaskEntity::class, 'st')
      ->where($qb->expr()->in('st.id', ':ids'))
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete the scheduled tasks subscribers
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "DELETE sts_top FROM $stsTable sts_top
      JOIN (
        SELECT sts.`task_id`, sts.`subscriber_id` FROM $stsTable sts
        WHERE `task_id` IN (:ids)
        LIMIT :limit
      ) as to_delete ON sts_top.`task_id` = to_delete.`task_id` AND sts_top.`subscriber_id` = to_delete.`subscriber_id`",
      ['limit' => self::DELETE_ROWS_LIMIT, 'ids' => $ids],
      ['limit' => ParameterType::INTEGER, 'ids' => ArrayParameterType::INTEGER]
    );


    $qb = $this->entityManager->createQueryBuilder();
    $qb->delete(ScheduledTaskSubscriberEntity::class, 'sts')
      ->where($qb->expr()->in('sts.task', ':ids'))
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    return $countDeletedTasks;
  }

  public function cleanupOrphanedScheduledTaskSubscribers(): int {
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $deletedCount = 0;

    $this->createOrphanedScheduledTaskSubscribersTemporaryTables();
    do {
      $deletedCount += (int)$this->entityManager->getConnection()->executeStatement(
        "
          DELETE sts_top FROM $stsTable sts_top
          JOIN (
            SELECT task_id, subscriber_id
            FROM $stsTable
            WHERE task_id IN (SELECT task_id FROM orphaned_task_ids)
            LIMIT :limit
          ) AS to_delete ON sts_top.task_id = to_delete.task_id AND sts_top.subscriber_id = to_delete.subscriber_id
        ",
        ['limit' => self::DELETE_ROWS_LIMIT],
        ['limit' => ParameterType::INTEGER]
      );
    } while ($this->getOrphanedScheduledTasksSubscribersCountFromTemporaryTables() > 0);
    $this->dropOrphanedScheduledTaskSubscribersTemporaryTables();
    return $deletedCount;
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

  public function cleanupOrphanedNewsletterLinks(): int {
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterLinkTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
    return (int)$this->entityManager->getConnection()->executeStatement("
      DELETE nl FROM $newsletterLinkTable nl
      LEFT JOIN $newsletterTable n ON n.`id` = nl.`newsletter_id`
      LEFT JOIN $sendingQueueTable sq ON sq.`id` = nl.`queue_id`
      WHERE n.`id` IS NULL OR sq.`id` IS NULL
    ");
  }

  public function cleanupOrphanedNewsletterPosts(): int {
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $newsletterPostTable = $this->entityManager->getClassMetadata(NewsletterPostEntity::class)->getTableName();
    return (int)$this->entityManager->getConnection()->executeStatement("
      DELETE np FROM $newsletterPostTable np
      LEFT JOIN $newsletterTable n ON n.`id` = np.`newsletter_id`
      WHERE n.`id` IS NULL
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

  private function createOrphanedScheduledTaskSubscribersTemporaryTables(): void {
    $connection = $this->entityManager->getConnection();
    $stTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $stsTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

    // 1. Get the DISTINCT task IDs so that the subsequent JOIN is more efficient.
    $connection->executeStatement("
      CREATE TEMPORARY TABLE IF NOT EXISTS task_ids
      SELECT DISTINCT task_id FROM $stsTable
    ");

    // 2. Get the orphaned task IDs.
    $connection->executeStatement("
      CREATE TEMPORARY TABLE IF NOT EXISTS orphaned_task_ids
      SELECT task_id FROM task_ids LEFT JOIN $stTable st ON st.id = task_ids.task_id WHERE st.id IS NULL
    ");
  }

  private function dropOrphanedScheduledTaskSubscribersTemporaryTables(): void {
    $this->entityManager->getConnection()->executeStatement("DROP TEMPORARY TABLE IF EXISTS task_ids");
    $this->entityManager->getConnection()->executeStatement("DROP TEMPORARY TABLE IF EXISTS orphaned_task_ids");
  }
}
