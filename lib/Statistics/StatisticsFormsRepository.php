<?php

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatisticsFormEntity;

/**
 * @extends Repository<StatisticsFormEntity>
 */
class StatisticsFormsRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsFormEntity::class;
  }
}
