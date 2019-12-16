<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\CustomFieldEntity;

class CustomFieldsResponseBuilder {

  /**
   * @param CustomFieldEntity[] $custom_fields
   * @return array
   */
  function buildBatch(array $custom_fields) {
    return array_map([$this, 'build'], $custom_fields);
  }

  /**
   * @param CustomFieldEntity $custom_field
   * @return array
   */
  function build(CustomFieldEntity $custom_field) {
    return [
      'id' => $custom_field->getId(),
      'name' => $custom_field->getName(),
      'type' => $custom_field->getType(),
      'params' => $custom_field->getParams(),
      'created_at' => $custom_field->getCreatedAt()->format('Y-m-d H:i:s'),
      'updated_at' => $custom_field->getUpdatedAt()->format('Y-m-d H:i:s'),
    ];
  }
}
