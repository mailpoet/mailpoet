<?php

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

/**
 * @method SettingEntity[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method SettingEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method SettingEntity|null findOneById(mixed $id)
 * @method SettingEntity[] findAll()
 * @method void persist(SettingEntity $entity)
 * @method void remove(SettingEntity $entity)
 */
class SettingsRepository extends Repository {
  public function findOneByName($name) {
    return $this->doctrine_repository->findOneBy(['name' => $name]);
  }

  public function createOrUpdateByName($name, $value) {
    // Temporarily use low-level INSERT ... ON DUPLICATE KEY UPDATE query to avoid race conditions
    // between entity fetch and creation with multiple concurrent requests. This will be replaced
    // by a code solving atomicity of create-or-update on entity (ORM) level in a follow-up ticket.
    $now = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $table_name = $this->entity_manager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->entity_manager->getConnection()->executeUpdate("
      INSERT INTO $table_name (name, value, created_at, updated_at)
      VALUES (:name, :value, :now, :now)
      ON DUPLICATE KEY UPDATE value = :value, updated_at = :now
    ", [
      'name' => $name,
      'value' => is_array($value) ? serialize($value) : $value,
      'now' => $now,
    ]);
    $this->entity_manager->clear(SettingEntity::class);
  }

  protected function getEntityClassName() {
    return SettingEntity::class;
  }
}
