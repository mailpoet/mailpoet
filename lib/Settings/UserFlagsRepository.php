<?php

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\UserFlagEntity;

/**
 * @extends Repository<UserFlagEntity>
 */
class UserFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return UserFlagEntity::class;
  }
}
