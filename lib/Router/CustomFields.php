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

















  function get($id) {
    $custom_field = CustomField::findOne($id);
    if($custom_field === false) {
      wp_send_json(false);
    } else {
      $custom_field = $custom_field->asArray();
      wp_send_json($custom_field);
    }
  }





























  function create() {
    // create new form
    $custom_field_data = array(
      'name' => __('New form'),
      'body' => array(
        array(
          'name' => __('Email'),
          'type' => 'input',
          'field' => 'email',
          'static' => true,
          'params' => array(
            'label' => __('Email'),
            'required' => true
          )
        ),
        array(
          'name' => __('Submit'),
          'type' => 'submit',
          'field' => 'submit',
          'static' => true,
          'params' => array(
            'label' => __('Subscribe!')
          )
        )
      ),
      'settings' => array(
        'on_success' => 'message',
        'success_message' => __('Check your inbox or spam folder now to confirm your subscription.'),
        'segments' => null,
        'segments_selected_by' => 'admin'
      )
    );

    $custom_field = CustomField::createOrUpdate($custom_field_data);

    if($custom_field !== false && $custom_field->id()) {
      wp_send_json(
        admin_url('admin.php?page=mailpoet-form-editor&id='.$custom_field->id())
      );
    } else {
      wp_send_json(false);
    }
  }

  function save($data = array()) {
    $custom_field = CustomField::createOrUpdate($data);

    if($custom_field !== false && $custom_field->id()) {
      wp_send_json($custom_field->id());
    } else {
      wp_send_json($custom_field);
    }
  }


  function restore($id) {
    $result = false;

    $custom_field = CustomField::findOne($id);
    if($custom_field !== false) {
      $result = $custom_field->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $custom_field = CustomField::findOne($id);
    if($custom_field !== false) {
      $result = $custom_field->trash();
    }

    wp_send_json($result);
  }


}
