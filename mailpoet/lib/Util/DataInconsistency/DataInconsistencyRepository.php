<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\ScheduledTaskEntity;
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

  public function cleanupOrphanedSendingTasks(): int {
    $ids = $this->buildOrphanedSendingTasksQuery(
      $this->entityManager->createQueryBuilder()
      ->select('st.id')
    )->getResult();

    $qb = $this->entityManager->createQueryBuilder();
    return $qb->delete(ScheduledTaskEntity::class, 'st')
      ->where($qb->expr()->in('st.id', ':ids'))
      ->setParameter('ids', array_column($ids, 'id'))
      ->getQuery()
      ->execute();
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
