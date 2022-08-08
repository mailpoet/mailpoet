<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<ScheduledTaskEntity>
 */
class ScheduledTasksRepository extends Repository {
  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByNewsletterAndStatus(NewsletterEntity $newsletter, string $status): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('st.status = :status')
      ->andWhere('sq.newsletter = :newsletter')
      ->setParameter('status', $status)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByScheduledAndRunningForNewsletter(NewsletterEntity $newsletter): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('st.status = :status OR st.status IS NULL')
      ->andWhere('sq.newsletter = :newsletter')
      ->setParameter('status', NewsletterEntity::STATUS_SCHEDULED)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByNewsletterAndSubscriberId(NewsletterEntity $newsletter, int $subscriberId): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->join(ScheduledTaskSubscriberEntity::class, 'sts', Join::WITH, 'st = sts.task')
      ->andWhere('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriberId)
      ->getQuery()
      ->getResult();
  }

  public function findScheduledOrRunningTask(?string $type): ?ScheduledTaskEntity {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('((st.status = :scheduledStatus) OR (st.status is NULL))')
      ->andWhere('st.deletedAt IS NULL')
      ->setParameter('scheduledStatus', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setMaxResults(1)
      ->orderBy('st.scheduledAt', 'DESC');
    if (!empty($type)) {
      $queryBuilder
        ->andWhere('st.type = :type')
        ->setParameter('type', $type);
    }
    return $queryBuilder->getQuery()->getOneOrNullResult();
  }

  public function findScheduledTask(?string $type): ?ScheduledTaskEntity {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.status = :scheduledStatus')
      ->andWhere('st.deletedAt IS NULL')
      ->setParameter('scheduledStatus', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setMaxResults(1)
      ->orderBy('st.scheduledAt', 'DESC');
    if (!empty($type)) {
      $queryBuilder
        ->andWhere('st.type = :type')
        ->setParameter('type', $type);
    }
    return $queryBuilder->getQuery()->getOneOrNullResult();
  }

  public function findPreviousTask(ScheduledTaskEntity $task): ?ScheduledTaskEntity {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.type = :type')
      ->setParameter('type', $task->getType())
      ->andWhere('st.createdAt < :created')
      ->setParameter('created', $task->getCreatedAt())
      ->orderBy('st.scheduledAt', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function findDueByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_SCHEDULED, $limit);
  }

  public function findRunningByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, null, $limit);
  }

  public function findCompletedByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_COMPLETED, $limit);
  }

  public function findFutureScheduledByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_SCHEDULED, $limit, true);
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findRunningSendingTasks(?int $limit = null): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join('st.sendingQueue', 'sq')
      ->where('st.type = :type')
      ->andWhere('st.status IS NULL')
      ->andWhere('st.deletedAt IS NULL')
      ->andWhere('sq.deletedAt IS NULL')
      ->orderBy('st.priority', 'ASC')
      ->addOrderBy('st.updatedAt', 'ASC')
      ->setMaxResults($limit)
      ->setParameter('type', ScheduledTaskEntity::TYPE_SENDING)
      ->getQuery()
      ->getResult();
  }

  protected function findByTypeAndStatus($type, $status, $limit = null, $future = false) {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.type = :type')
      ->setParameter('type', $type)
      ->andWhere('st.deletedAt IS NULL');

    if (is_null($status)) {
      $queryBuilder->andWhere('st.status IS NULL');
    } else {
      $queryBuilder
        ->andWhere('st.status = :status')
        ->setParameter('status', $status);
    }

    if ($future) {
      $queryBuilder->andWhere('st.scheduledAt > :now');
    } else {
      $queryBuilder->andWhere('st.scheduledAt <= :now');
    }

    $now = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queryBuilder->setParameter('now', $now);

    if ($limit) {
      $queryBuilder->setMaxResults($limit);
    }

    return $queryBuilder->getQuery()->getResult();
  }

  protected function getEntityClassName() {
    return ScheduledTaskEntity::class;
  }
}
