<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\CustomFieldEntity;

class CustomFieldsResponseBuilder {
  /**
   * @param CustomFieldEntity[] $customFields
   * @return array
   */
  public function buildBatch(array $customFields) {
    return array_map([$this, 'build'], $customFields);
  }

  /**
   * @param CustomFieldEntity $customField
   * @return array
   */
  public function build(CustomFieldEntity $customField) {
    return [
      'id' => $customField->getId(),
      'name' => $customField->getName(),
      'type' => $customField->getType(),
      'params' => $customField->getParams(),
      'created_at' => $customField->getCreatedAt()->format('Y-m-d H:i:s'),
      'updated_at' => $customField->getUpdatedAt()->format('Y-m-d H:i:s'),
    ];
  }
}
