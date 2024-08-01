<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
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
