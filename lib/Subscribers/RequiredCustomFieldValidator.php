<?php
namespace MailPoet\Subscribers;

use Exception;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\WP\Functions as WPFunctions;

class RequiredCustomFieldValidator {

  /**
   * @param array $data
   * @param Form|null $form
   *
   * @throws Exception
   */
  public function validate(array $data, Form $form = null) {
    $all_custom_fields = $this->getCustomFields($form);
    foreach ($all_custom_fields as $custom_field_id => $custom_field_name) {
      if ($this->isCustomFieldMissing($custom_field_id, $data)) {
        throw new Exception(
          WPFunctions::get()->__(sprintf('Missing value for custom field "%s"', $custom_field_name), 'mailpoet')
        );
      }
    }
  }

  private function isCustomFieldMissing($custom_field_id, $data) {
    if (!array_key_exists($custom_field_id, $data) && !array_key_exists('cf_' . $custom_field_id, $data)) {
      return true;
    }
    if (isset($data[$custom_field_id]) && !$data[$custom_field_id]) {
      return true;
    }
    if (isset($data['cf_' . $custom_field_id]) && !$data['cf_' . $custom_field_id]) {
      return true;
    }
    return false;
  }

  private function getCustomFields(Form $form = null) {
    $result = [];

    if ($form) {
      $ids = $this->getFormCustomFieldIds($form);
      if (!$ids) {
        return [];
      }
      $required_custom_fields = CustomField::whereIn('id', $ids)->findMany();
    } else {
      $required_custom_fields = CustomField::findMany();
    }

    foreach ($required_custom_fields as $custom_field) {
      if (is_serialized($custom_field->params)) {
        $params = unserialize($custom_field->params);
        if (is_array($params) && isset($params['required']) && $params['required']) {
          $result[$custom_field->id] = $custom_field->name;
        }
      }
    }

    return $result;
  }

  private function getFormCustomFieldIds(Form $form) {
    $form_fields = $form->getFieldList();
    $custom_field_ids = [];
    foreach ($form_fields as $field_name) {
      if (strpos($field_name, 'cf_') === 0) {
        $custom_field_ids[] = (int)substr($field_name, 3);
      }
    }
    return $custom_field_ids;
  }

}
