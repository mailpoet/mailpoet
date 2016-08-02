<?php
namespace MailPoet\API\Endpoints;

use \MailPoet\Models\CustomField;

if(!defined('ABSPATH')) exit;

class CustomFields {
  function __construct() {
  }

  function getAll() {
    $collection = CustomField::findMany();
    $custom_fields = array_map(function($custom_field) {
      return $custom_field->asArray();
    }, $collection);

    return $custom_fields;
  }

  function delete($id) {
    $custom_field = CustomField::findOne($id);
    if($custom_field === false or !$custom_field->id()) {
      return array('result' => false);
    } else {
      $custom_field->delete();

      return array(
        'result' => true,
        'field' => $custom_field->asArray()
      );
    }
  }

  function save($data = array()) {
    $custom_field = CustomField::createOrUpdate($data);
    $errors = $custom_field->getErrors();

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
      return array(
        'result' => true,
        'field' => $custom_field->asArray()
      );
    }
  }

  function get($id) {
    $custom_field = CustomField::findOne($id);
    if($custom_field === false) {
      return false;
    } else {
      return $custom_field->asArray();
    }
  }
}