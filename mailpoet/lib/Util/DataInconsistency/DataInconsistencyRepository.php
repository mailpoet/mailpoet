<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class DataInconsistencyRepository {
  private EntityManager $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function getOrphanedSendingTasksCount(): int {
    return (int)$this->entityManager->createQueryBuilder()
      ->select('count(st.id)')
      ->from(ScheduledTaskEntity::class, 'st')
      ->leftJoin('st.sendingQueue', 'sq')
      ->where('sq.id IS NULL')
      ->andWhere('st.type = :type')
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->getQuery()
      ->getSingleScalarResult();
  }
}
