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
    $allCustomFields = $this->getCustomFields($form);
    foreach ($allCustomFields as $customFieldId => $customFieldName) {
      if ($this->isCustomFieldMissing($customFieldId, $data)) {
        throw new Exception(
          WPFunctions::get()->__(sprintf('Missing value for custom field "%s"', $customFieldName), 'mailpoet')
        );
      }
    }
  }

  private function isCustomFieldMissing($customFieldId, $data) {
    if (!array_key_exists($customFieldId, $data) && !array_key_exists('cf_' . $customFieldId, $data)) {
      return true;
    }
    if (isset($data[$customFieldId]) && !$data[$customFieldId]) {
      return true;
    }
    if (isset($data['cf_' . $customFieldId]) && !$data['cf_' . $customFieldId]) {
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
      $requiredCustomFields = CustomField::whereIn('id', $ids)->findMany();
    } else {
      $requiredCustomFields = CustomField::findMany();
    }

    foreach ($requiredCustomFields as $customField) {
      if (is_serialized($customField->params)) {
        $params = unserialize($customField->params);
        if (is_array($params) && isset($params['required']) && $params['required']) {
          $result[$customField->id] = $customField->name;
        }
      }
    }

    return $result;
  }

  private function getFormCustomFieldIds(Form $form) {
    $formFields = $form->getFieldList();
    $customFieldIds = [];
    foreach ($formFields as $fieldName) {
      if (strpos($fieldName, 'cf_') === 0) {
        $customFieldIds[] = (int)substr($fieldName, 3);
      }
    }
    return $customFieldIds;
  }

}
