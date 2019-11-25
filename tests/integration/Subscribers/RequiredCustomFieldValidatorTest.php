<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoetVendor\Idiorm\ORM;

class RequiredCustomFieldValidatorTest extends \MailPoetTest {

  private $custom_field;

  function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    $this->custom_field = CustomField::createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
  }

  function testItValidatesDataWithoutCustomField() {
    $validator = new RequiredCustomFieldValidator();
    $this->setExpectedException('Exception');
    $validator->validate([]);
  }

  function testItValidatesDataWithCustomFieldPassedAsId() {
    $validator = new RequiredCustomFieldValidator();
    $validator->validate([$this->custom_field->id() => 'value']);
  }

  function testItValidatesDataWithCustomFieldPassedAsCFId() {
    $validator = new RequiredCustomFieldValidator();
    $validator->validate(['cf_' . $this->custom_field->id() => 'custom field']);
  }

  function testItValidatesDataWithEmptyCustomField() {
    $validator = new RequiredCustomFieldValidator();
    $this->setExpectedException('Exception');
    $validator->validate([$this->custom_field->id() => '']);
  }

  function testItValidatesDataWithEmptyCustomFieldAsCFId() {
    $validator = new RequiredCustomFieldValidator();
    $this->setExpectedException('Exception');
    $validator->validate(['cf_' . $this->custom_field->id() => '']);
  }

  function testItValidatesOnlyFieldPresentInForm() {
    CustomField::createOrUpdate([
      'name' => 'custom field 2',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
    $form = Form::createOrUpdate([
      'name' => 'form',
      'body' => [[
        'type' => 'text',
        'name' => 'mandatory',
        'id' => $this->custom_field->id(),
        'unique' => '1',
        'static' => '0',
        'params' => ['required' => '1'],
        'position' => '0',
      ]],
    ]);
    $validator = new RequiredCustomFieldValidator();
    $validator->validate(['cf_' . $this->custom_field->id() => 'value'], $form);
  }

}
