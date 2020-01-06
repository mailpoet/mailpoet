<?php

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\UserFlagEntity;

/**
 * @method UserFlagEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method UserFlagEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFlagEntity|null findOneById(mixed $id)
 * @method void persist(UserFlagEntity $entity)
 * @method void remove(UserFlagEntity $entity)
 */
class UserFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return UserFlagEntity::class;
  }
}
