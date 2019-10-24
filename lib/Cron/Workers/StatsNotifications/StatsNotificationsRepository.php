<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatsNotificationEntity;

class StatsNotificationsRepository extends Repository {
  protected function getEntityClassName() {
    return StatsNotificationEntity::class;
  }

  /**
   * @param int $newsletter_id
   * @return array
   */
  public function findAllForNewsletter($newsletter_id) {
    return $this->doctrine_repository
      ->createQueryBuilder('stn')
      ->join('stn.task', 'tasks')
      ->join('stn.newsletter', 'n')
      ->addSelect('tasks')
      ->where('tasks.type = :taskType')
      ->setParameter('taskType', Worker::TASK_TYPE)
      ->andWhere('n.id = :newsletterId')
      ->setParameter('newsletterId', $newsletter_id)
      ->getQuery()
      ->getResult();
  }
}
