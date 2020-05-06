<?php

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;

/**
 * @method StatisticsWooCommercePurchaseEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method StatisticsWooCommercePurchaseEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatisticsWooCommercePurchaseEntity|null findOneById(mixed $id)
 * @method void persist(StatisticsWooCommercePurchaseEntity $entity)
 * @method void remove(StatisticsWooCommercePurchaseEntity $entity)
 */
class StatisticsWooCommercePurchasesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsWooCommercePurchaseEntity::class;
  }
}
