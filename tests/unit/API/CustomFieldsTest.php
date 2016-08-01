<?php

use \MailPoet\API\CustomFields;
use \MailPoet\Models\CustomField;

class CustomFieldsTest extends MailPoetTest {
  private $custom_fields = array(
    array(
      'name' => 'CF: text',
      'type' => 'text',
      'params' => array(
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text'
      )
    ),
    array(
      'name' => 'CF: textarea',
      'type' => 'textarea',
      'params' => array(
        'required' => '1',
        'validate' => '',
        'label' => 'CF: text area'
      )
    ),
    array(
      'name' => 'CF: radio',
      'type' => 'radio',
      'params' => array(
        'values' =>
        array(
          array('value' => 'one'),
          array('value' => 'two'),
          array('value' => 'three')
        ),
        'required' => '1',
        'label' => 'CF: radio'
      )
    ),
    array(
      'name' => 'CF: date',
      'type' => 'date',
      'params' => array(
        'required' => '1',
        'date_type' => 'year_month_day',
        'date_format' => '',
        'label' => 'CF: date'
      )
    )
  );

  function _before() {
    foreach($this->custom_fields as $custom_field) {
      CustomField::createOrUpdate($custom_field);
    }
  }

  function testItCanGetAllCustomFields() {
    $router = new CustomFields();
    $response = $router->getAll();
    expect($response)->count(count($this->custom_fields));

    foreach($response as $custom_field) {
      expect($custom_field['name'])->notEmpty();
      expect($custom_field['type'])->notEmpty();
      expect($custom_field['params'])->notEmpty();
    }
  }

  function testItCanDeleteACustomField() {
    $custom_field = CustomField::where('type', 'date')->findOne();
    $custom_field_id = $custom_field->id();

    $router = new CustomFields();
    $response = $router->delete($custom_field_id);
    expect($response['result'])->true();

    $custom_field = CustomField::where('type', 'date')->findOne();
    expect($custom_field)->false();

    $response = $router->delete($custom_field_id);
    expect($response['result'])->false();
  }

  function testItCanSaveACustomField() {
    $new_custom_field = array(
      'name' => 'New custom field',
      'type' => 'text'
    );

    $router = new CustomFields();
    $response = $router->save($new_custom_field);
    expect($response['result'])->true();

    // missing type
    $response = $router->save(array('name' => 'New custom field'));
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a type');

    // missing name
    $response = $router->save(array('type' => 'text'));
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a name');

    // missing data
    $response = $router->save();
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a name');
    expect($response['errors'][1])->equals('Please specify a type');
  }

  function testItCanGetACustomField() {
    $custom_field = CustomField::where('name', 'CF: text')->findOne();

    $router = new CustomFields();
    $response = $router->get($custom_field->id());
    expect($response)->notEmpty();
    expect($response['name'])->equals('CF: text');
    expect($response['type'])->equals('text');
    expect($response['params'])->notEmpty();

    $response = $router->get('not_an_id');
    expect($response)->false();
  }

  function _after() {
    CustomField::deleteMany();
  }
}
