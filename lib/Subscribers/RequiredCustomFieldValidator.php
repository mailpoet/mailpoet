<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\CustomField;

class RequiredCustomFieldValidator {

  /**
   * @param array $data
   *
   * @throws \Exception
   */
  public function validate(array $data) {
    $all_custom_fields = $this->getCustomFields();
    foreach($all_custom_fields as $custom_field_id => $custom_field_name) {
      if($this->isCustomFieldMissing($custom_field_id, $data)) {
        throw new \Exception(
          __(sprintf('Missing value for custom field "%s"', $custom_field_name), 'mailpoet')
        );
      }
    }
  }

  private function isCustomFieldMissing($custom_field_id, $data) {
    if(!array_key_exists($custom_field_id, $data) && !array_key_exists('cf_' . $custom_field_id, $data)) {
      return true;
    }
    if(isset($data[$custom_field_id]) && !$data[$custom_field_id]) {
      return true;
    }
    if(isset($data['cf_' . $custom_field_id]) && !$data['cf_' . $custom_field_id]) {
      return true;
    }
    return false;
  }

  private function getCustomFields() {
    $result = [];

    $required_custom_fields = CustomField::findMany();

    foreach($required_custom_fields as $custom_field) {
      if(is_serialized($custom_field->params)) {
        $params = unserialize($custom_field->params);
        if(is_array($params) && isset($params['required']) && $params['required']) {
          $result[$custom_field->id] = $custom_field->name;
        }
      }
    }

    return $result;
  }

}
