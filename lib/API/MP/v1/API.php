<?php
namespace MailPoet\API\MP\v1;

use MailPoet\Models\CustomField;

if(!defined('ABSPATH')) exit;

class API {
  function getSubscriberFields() {
    $data = array(
      array(
        'id' => 'email',
        'name' => __('Email', 'mailpoet')
      ),
      array(
        'id' => 'first_name',
        'name' => __('First name', 'mailpoet')
      ),
      array(
        'id' => 'last_name',
        'name' => __('Last name', 'mailpoet')
      )
    );

    $custom_fields = CustomField::selectMany(array('id', 'name'))->findMany();
    foreach($custom_fields as $custom_field) {
      $data[] = array(
        'id' => 'cf_' . $custom_field->id,
        'name' => $custom_field->name
      );
    }

    return $data;
  }
}