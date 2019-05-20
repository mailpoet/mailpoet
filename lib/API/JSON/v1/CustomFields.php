<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Models\CustomField;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class CustomFields extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  function getAll() {
    $collection = CustomField::orderByAsc('created_at')->findMany();
    $custom_fields = array_map(function($custom_field) {
      return $custom_field->asArray();
    }, $collection);

    return $this->successResponse($custom_fields);
  }

  function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = CustomField::findOne($id);
    if ($custom_field instanceof CustomField) {
      $custom_field->delete();

      return $this->successResponse($custom_field->asArray());
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This custom field does not exist.', 'mailpoet'),
      ]);
    }
  }

  function save($data = []) {
    $custom_field = CustomField::createOrUpdate($data);
    $errors = $custom_field->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    }
    $custom_field = CustomField::findOne($custom_field->id);
    if(!$custom_field instanceof CustomField) return $this->errorResponse();
    return $this->successResponse($custom_field->asArray());
  }

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = CustomField::findOne($id);
    if ($custom_field instanceof CustomField) {
      return $this->successResponse($custom_field->asArray());
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This custom field does not exist.', 'mailpoet'),
    ]);
  }
}
