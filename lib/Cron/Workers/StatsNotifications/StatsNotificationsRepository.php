<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoetVendor\Carbon\Carbon;

/**
 * @extends Repository<StatsNotificationEntity>
 */
class StatsNotificationsRepository extends Repository {
  protected function getEntityClassName() {
    return StatsNotificationEntity::class;
  }

  /**
   * @param int $newsletterId
   * @return StatsNotificationEntity|null
   */
  public function findOneByNewsletterId($newsletterId) {
    return $this->doctrineRepository
      ->createQueryBuilder('sn')
      ->andWhere('sn.newsletter = :newsletterId')
      ->setParameter('newsletterId', $newsletterId)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * @param int|null $limit
   * @return StatsNotificationEntity[]
   */
  public function findScheduled($limit = null) {
    $date = new Carbon();
    $query = $this->doctrineRepository
      ->createQueryBuilder('sn')
      ->join('sn.task', 'tasks')
      ->join('sn.newsletter', 'n')
      ->addSelect('tasks')
      ->addSelect('n')
      ->addOrderBy('tasks.priority')
      ->addOrderBy('tasks.updatedAt')
      ->where('tasks.deletedAt IS NULL')
      ->andWhere('tasks.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->andWhere('tasks.scheduledAt < :date')
      ->setParameter('date', $date)
      ->andWhere('tasks.type = :workerType')
      ->setParameter('workerType', Worker::TASK_TYPE);
    if (is_int($limit)) {
      $query->setMaxResults($limit);
    }
    return $query->getQuery()->getResult();
  }
}
