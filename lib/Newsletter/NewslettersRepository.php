<?php

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Doctrine\Repository;

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
}
