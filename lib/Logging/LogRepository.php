<?php

namespace MailPoet\Logging;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\LogEntity;

/**
 * @extends Repository<LogEntity>
 */
class LogRepository extends Repository {
  protected function getEntityClassName() {
    return LogEntity::class;
  }
}
