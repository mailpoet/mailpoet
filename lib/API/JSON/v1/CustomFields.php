<?php
namespace MailPoet\API\JSON\v1;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Models\CustomField;

if(!defined('ABSPATH')) exit;

class CustomFields extends APIEndpoint {
  function getAll() {
    $collection = CustomField::orderByAsc('created_at')->findMany();
    $custom_fields = array_map(function($custom_field) {
      return $custom_field->asArray();
    }, $collection);

    return $this->successResponse($custom_fields);
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = CustomField::findOne($id);
    if($custom_field === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This custom field does not exist.', 'mailpoet')
      ));
    } else {
      $custom_field->delete();

      return $this->successResponse($custom_field->asArray());
    }
  }

  function save($data = array()) {
    $custom_field = CustomField::createOrUpdate($data);
    $errors = $custom_field->getErrors();

    if(!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      return $this->successResponse(
        CustomField::findOne($custom_field->id)->asArray()
      );
    }
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = CustomField::findOne($id);
    if($custom_field === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This custom field does not exist.', 'mailpoet')
      ));
    } else {
      return $this->successResponse($custom_field->asArray());
    }
  }
}