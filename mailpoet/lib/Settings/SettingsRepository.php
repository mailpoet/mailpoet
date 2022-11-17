<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

/**
 * @extends Repository<SettingEntity>
 */
class SettingsRepository extends Repository {
  public function findOneByName($name) {
    return $this->doctrineRepository->findOneBy(['name' => $name]);
  }

  public function createOrUpdateByName($name, $value) {
    // Temporarily use low-level INSERT ... ON DUPLICATE KEY UPDATE query to avoid race conditions
    // between entity fetch and creation with multiple concurrent requests. This will be replaced
    // by a code solving atomicity of create-or-update on entity (ORM) level in a follow-up ticket.
    $now = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $tableName = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("
      INSERT INTO $tableName (name, value, created_at, updated_at)
      VALUES (:name, :value, :now, :now)
      ON DUPLICATE KEY UPDATE value = :value, updated_at = :now
    ", [
      'name' => $name,
      'value' => is_array($value) ? serialize($value) : $value,
      'now' => $now,
    ]);
    $this->entityManager->getUnitOfWork()->clear(SettingEntity::class);
  }

  protected function getEntityClassName() {
    return SettingEntity::class;
  }
}
