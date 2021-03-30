<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;

/**
 * @extends Repository<SendingQueueEntity>
 */
class SendingQueuesRepository extends Repository {
  protected function getEntityClassName() {
    return SendingQueueEntity::class;
  }

  public function findOneByNewsletterAndTaskStatus(NewsletterEntity $newsletter, string $status): ?SendingQueueEntity {
    return $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SendingQueueEntity::class, 's')
      ->join('s.task', 't')
      ->where('t.status = :status')
      ->andWhere('s.newsletter = :newsletter')
      ->setParameter('status', $status)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getOneOrNullResult();
  }
}
