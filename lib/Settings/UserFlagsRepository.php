<?php

namespace MailPoet\Settings;

use MailPoet\Entities\UserFlagEntity;
use MailPoet\Doctrine\Repository;

/**
 * @method UserFlagEntity[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method UserFlagEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method UserFlagEntity|null findOneById(mixed $id)
 * @method void persist(UserFlagEntity $entity)
 * @method void remove(UserFlagEntity $entity)
 */
class UserFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return UserFlagEntity::class;
  }
}
