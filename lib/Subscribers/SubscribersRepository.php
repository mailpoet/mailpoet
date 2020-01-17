<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberEntity;

/**
 * @method SubscriberEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method SubscriberEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriberEntity|null findOneById(mixed $id)
 * @method void persist(SubscriberEntity $entity)
 * @method void remove(SubscriberEntity $entity)
 */
class SubscribersRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  /**
   * @return int
   */
  public function getTotalSubscribers() {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('count(n.id)')
      ->from(SubscriberEntity::class, 'n')
      ->where('n.deletedAt IS NULL AND n.status IN (:statuses)')
      ->setParameter('statuses', [
        SubscriberEntity::STATUS_SUBSCRIBED,
        SubscriberEntity::STATUS_UNCONFIRMED,
        SubscriberEntity::STATUS_INACTIVE,
      ])
      ->getQuery();
    return (int)$query->getSingleScalarResult();
  }

  /**
   * @return int
   */
  public function getTotalSubscribersWithoutWPUsers() {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('count(n.id)')
      ->from(SubscriberEntity::class, 'n')
      ->where('n.deletedAt IS NULL AND n.status IN (:statuses) AND n.wpUserId IS NULL')
      ->setParameter('statuses', [
        SubscriberEntity::STATUS_SUBSCRIBED,
        SubscriberEntity::STATUS_UNCONFIRMED,
        SubscriberEntity::STATUS_INACTIVE,
      ])
      ->getQuery();
    return (int)$query->getSingleScalarResult();
  }
}
