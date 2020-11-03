<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
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

  protected function getEntityClassName() {
    return ScheduledTaskEntity::class;
  }
}
