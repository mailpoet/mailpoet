<?php

namespace MailPoet\Newsletter;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;

/**
 * @method NewsletterEntity[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method NewsletterEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method NewsletterEntity|null findOneById(mixed $id)
 * @method void persist(NewsletterEntity $entity)
 * @method void remove(NewsletterEntity $entity)
 */
class NewslettersRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  /**
   * @param string[] $types
   * @return NewsletterEntity[]
   */
  public function findActiveByTypes($types) {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->setParameter(':status', NewsletterEntity::STATUS_ACTIVE)
      ->andWhere('n.deleted_at is null')
      ->andWhere('n.type IN (:types)')
      ->setParameter('types', $types)
      ->orderBy('n.subject')
      ->getQuery()
      ->getResult();
  }
}
