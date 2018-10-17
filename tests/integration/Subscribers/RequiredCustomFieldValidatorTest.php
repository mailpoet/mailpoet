<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\CustomField;

class RequiredCustomFieldValidatorTest extends \MailPoetTest {

  private $custom_field;

  function _before() {
    \ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    $this->custom_field = CustomField::createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1']
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

}