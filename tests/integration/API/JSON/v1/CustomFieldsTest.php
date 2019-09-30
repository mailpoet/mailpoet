<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\CustomFields;
use MailPoet\Models\CustomField;

class CustomFieldsTest extends \MailPoetTest {
  private $custom_fields = [
    [
      'name' => 'CF: text',
      'type' => 'text',
      'params' => [
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text',
      ],
    ],
    [
      'name' => 'CF: textarea',
      'type' => 'textarea',
      'params' => [
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text area',
      ],
    ],
    [
      'name' => 'CF: radio',
      'type' => 'radio',
      'params' => [
        'values' =>
        [
          ['value' => 'one'],
          ['value' => 'two'],
          ['value' => 'three'],
        ],
        'required' => '1',
        'label' => 'CF: radio',
      ],
    ],
    [
      'name' => 'CF: date',
      'type' => 'date',
      'params' => [
        'required' => '1',
        'date_type' => 'year_month_day',
        'date_format' => '',
        'label' => 'CF: date',
      ],
    ],
  ];

  function _before() {
    parent::_before();
    foreach ($this->custom_fields as $custom_field) {
      CustomField::createOrUpdate($custom_field);
    }
  }

  function testItCanGetAllCustomFields() {
    $router = new CustomFields();
    $response = $router->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count(count($this->custom_fields));

    foreach ($response->data as $custom_field) {
      expect($custom_field['name'])->notEmpty();
      expect($custom_field['type'])->notEmpty();
      expect($custom_field['params'])->notEmpty();
    }
  }

  function testItCanDeleteACustomField() {
    $custom_field = CustomField::where('type', 'date')->findOne();
    $custom_field_id = $custom_field->id();

    $router = new CustomFields();
    $response = $router->delete(['id' => $custom_field_id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $custom_field = CustomField::where('type', 'date')->findOne();
    expect($custom_field)->false();

    $response = $router->delete(['id' => $custom_field_id]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItCanSaveACustomField() {
    $new_custom_field = [
      'name' => 'New custom field',
      'type' => 'text',
    ];

    $router = new CustomFields();
    $response = $router->save($new_custom_field);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    // missing type
    $response = $router->save(['name' => 'New custom field']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a type.');

    // missing name
    $response = $router->save(['type' => 'text']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');

    // missing data
    $response = $router->save();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');
    expect($response->errors[1]['message'])->equals('Please specify a type.');
  }

  function testItCanGetACustomField() {
    $custom_field = CustomField::where('name', 'CF: text')->findOne();

    $router = new CustomFields();
    $response = $router->get(['id' => $custom_field->id()]);

    expect($response->data['name'])->equals('CF: text');
    expect($response->data['type'])->equals('text');
    expect($response->data['params'])->notEmpty();

    $response = $router->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function _after() {
    CustomField::deleteMany();
  }
}
