<?php

namespace MailPoet\CustomFields;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\CustomFieldEntity;

/**
 * @extends Repository<CustomFieldEntity>
 */
class CustomFieldsRepository extends Repository {
  protected function getEntityClassName() {
    return CustomFieldEntity::class;
  }

  /**
   * @param array $data
   * @return CustomFieldEntity
   */
  public function createOrUpdate($data) {
    if (isset($data['id'])) {
      $field = $this->findOneById((int)$data['id']);
    } elseif (isset($data['name'])) {
      $field = $this->findOneBy(['name' => $data['name']]);
    }
    if (!isset($field)) {
      $field = new CustomFieldEntity();
      $this->entityManager->persist($field);
    }
    if (isset($data['name'])) $field->setName($data['name']);
    if (isset($data['type'])) $field->setType($data['type']);
    if (isset($data['params'])) $field->setParams($data['params']);
    $this->entityManager->flush();
    return $field;
  }
}
