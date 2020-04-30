<?php

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatisticsUnsubscribeEntity;

/**
 * @method StatisticsUnsubscribeEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method StatisticsUnsubscribeEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatisticsUnsubscribeEntity|null findOneById(mixed $id)
 * @method void persist(StatisticsUnsubscribeEntity $entity)
 * @method void remove(StatisticsUnsubscribeEntity $entity)
 */
class StatisticsUnsubscribesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsUnsubscribeEntity::class;
  }
}
