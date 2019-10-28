<?php

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;

/**
 * @method SettingEntity[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method SettingEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method SettingEntity|null findOneById(mixed $id)
 * @method SettingEntity[] findAll()
 * @method void persist(SettingEntity $entity)
 * @method void remove(SettingEntity $entity)
 */
class SettingsRepository extends Repository {
  protected function getEntityClassName() {
    return SettingEntity::class;
  }
}
