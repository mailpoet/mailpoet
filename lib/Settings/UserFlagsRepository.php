<?php

namespace MailPoet\Settings;

use MailPoet\Doctrine\Entities\UserFlag;
use MailPoet\Doctrine\Repository;

/**
 * @method UserFlag[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method UserFlag|null findOneBy(array $criteria, array $order_by = null)
 * @method UserFlag|null findOneById(mixed $id)
 * @method void persist(UserFlag $entity)
 * @method void remove(UserFlag $entity)
 */
class UserFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return UserFlag::class;
  }
}
