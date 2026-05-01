<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SettingEntity;

/**
 * @extends Repository<SettingEntity>
 */
class SettingsRepository extends Repository {
  protected function getEntityClassName(): string {
    return SettingEntity::class;
  }
}
