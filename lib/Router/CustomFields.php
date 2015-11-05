<?php
namespace MailPoet\Router;
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

    wp_send_json($custom_fields);
  }

  function delete($id) {
    $result = false;

    $custom_field = CustomField::findOne($id);
    if($custom_field !== false) {
      $custom_field->delete();
      $result = true;
    }

    wp_send_json($result);
  }

  function save($data = array()) {
    $custom_field = CustomField::createOrUpdate($data);

    if($custom_field === false) {
      $result = array(
        'result' => false,
        'errors' => array(
          __('The custom field could not be created.')
        )
      );
    } else {
      $errors = $custom_field->getValidationErrors();
      if(!empty($errors)) {
        $result = array(
          'result' => false,
          'errors' => $errors
        );
      } else {
        $result = array(
          'result' => true,
          'field' => $custom_field->asArray()
        );
      }
    }

    wp_send_json($result);
  }

  function get($id) {
    $custom_field = CustomField::findOne($id);
    if($custom_field === false) {
      wp_send_json(false);
    } else {
      $custom_field = $custom_field->asArray();
      wp_send_json($custom_field);
    }
  }
}