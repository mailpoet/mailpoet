<?php

namespace MailPoet\CustomFields;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\CustomFieldEntity;

/**
 * @method CustomFieldEntity[] findBy(array $criteria, array $order_by = null, int $limit = null, int $offset = null)
 * @method CustomFieldEntity[] findAll()
 * @method CustomFieldEntity|null findOneBy(array $criteria, array $order_by = null)
 * @method CustomFieldEntity|null findOneById(mixed $id)
 * @method void persist(CustomFieldEntity $entity)
 * @method void remove(CustomFieldEntity $entity)
 */
class CustomFieldsRepository extends Repository {
  protected function getEntityClassName() {
    return CustomFieldEntity::class;
  }
}
