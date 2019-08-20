<?php

namespace MailPoet\Settings;

use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Doctrine\Repository;

/**
 * @method FeatureFlagEntity[] findAll()
 * @method FeatureFlagEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method void persist(FeatureFlagEntity $entity)
 * @method void remove(FeatureFlagEntity $entity)
 */
class FeatureFlagsRepository extends Repository {
  protected function getEntityClassName() {
    return FeatureFlagEntity::class;
  }
}
