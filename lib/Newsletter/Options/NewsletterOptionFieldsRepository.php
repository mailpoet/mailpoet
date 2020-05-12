<?php

namespace MailPoet\Newsletter\Options;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterOptionFieldEntity;

/**
 * @method NewsletterOptionFieldEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method NewsletterOptionFieldEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsletterOptionFieldEntity|null findOneById(mixed $id)
 * @method void persist(NewsletterOptionFieldEntity $entity)
 * @method void remove(NewsletterOptionFieldEntity $entity)
 */
class NewsletterOptionFieldsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterOptionFieldEntity::class;
  }
}
