<?php

namespace MailPoet\Newsletter\Options;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterOptionEntity;

/**
 * @method NewsletterOptionEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method NewsletterOptionEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsletterOptionEntity|null findOneById(mixed $id)
 * @method void persist(NewsletterOptionEntity $entity)
 * @method void remove(NewsletterOptionEntity $entity)
 */
class NewsletterOptionsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterOptionEntity::class;
  }
}
