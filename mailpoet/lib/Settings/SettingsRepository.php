<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Settings;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Query;

/**
 * @extends Repository<SettingEntity>
 */
class SettingsRepository extends Repository {
  public function findOneByName(string $name): ?SettingEntity {
    // Always fetch fresh entity data (= don't use "findOneBy()"). See also further below.
    $result = (array)$this->doctrineRepository->createQueryBuilder('s')
      ->where('s.name = :name')
      ->setParameter('name', $name)
      ->getQuery()
      ->setHint(Query::HINT_REFRESH, true)
      ->getResult();

    return isset($result[0]) && $result[0] instanceof SettingEntity ? $result[0] : null;
  }

  public function createOrUpdateByName($name, $value) {
    // Temporarily use low-level INSERT ... ON DUPLICATE KEY UPDATE query to avoid race conditions
    // between entity fetch and creation with multiple concurrent requests. This will be replaced
    // by a code solving atomicity of create-or-update on entity (ORM) level in a follow-up ticket.
    $now = Carbon::now()->millisecond(0);
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
  }

  protected function getEntityClassName() {
    return SettingEntity::class;
  }
}
