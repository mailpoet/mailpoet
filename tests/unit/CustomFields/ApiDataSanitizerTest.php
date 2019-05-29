<?php

namespace MailPoet\CustomFields;

use InvalidArgumentException;

class ApiDataSanitizerTest extends \MailPoetUnitTest {

  /** @var ApiDataSanitizer */
  private $sanitizer;

  function _before() {
    $this->sanitizer = new ApiDataSanitizer();
  }

  function testItThrowsIfNameIsMissing() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['type' => 'text']);
  }

  function testItThrowsIfNameIsEmpty() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => '', 'type' => 'text']);
  }

  function testItThrowsIfNameIsWrongType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => ['x'], 'type' => 'text']);
  }

  function testItThrowsIfTypeIsMissing() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name']);
  }

  function testItThrowsIfTypeIsEmpty() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => '']);
  }

  function testItThrowsIfTypeIsWrongType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => ['y']]);
  }

  function testItThrowsIfTypeIsInvalid() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'Invalid Type']);
  }

  function testItThrowsIfParamsIsInvalidType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => 'xyz']);
  }

  function testItReturnsArray() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result)->internalType('array');
  }

  function testItReturnsName() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result)->hasKey('name');
    expect($result['name'])->same('Name');
  }

  function testItReturnsType() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'Text']);
    expect($result)->hasKey('type');
    expect($result['type'])->same('text');
  }

  function testItIgnoresUnknownProperties() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'unknown' => 'Unknown property']);
    expect($result)->hasntKey('unknown');
  }

  function testItReturnsParamsIfPassed() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => '1']]);
    expect($result)->hasKey('params');
  }

  function testItReturnsCorrectRequiredForm() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => true]]);
    expect($result['params']['required'])->same('1');
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => false]]);
    expect($result['params']['required'])->same('');
  }

  function testItIgnoresUnknownParams() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['unknown' => 'Unknown property']]);
    expect($result)->hasKey('params');
    expect($result['params'])->hasntKey('unknown');
  }

  function testItFillsLabel() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result['params'])->hasKey('label');
    expect($result['params']['label'])->same('Name');
  }

  function testItThrowsForInvalidValidate() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['validate' => 'unknown']]);
  }

  function testItReturnsSanitizedValidate() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['validate' => 'alphanuM']]);
    expect($result['params']['validate'])->same('alphanum');
  }

  function testItThrowsIfNoValuesInRadio() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'radio',
    ]);
  }

  function testItReturnsSanitizedValuesForRadio() {
    $result = $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'radio',
      'params' => [
        'values' => [
          [
            'value' => 'value 1',
            'unknown' => 'Unknown property',
          ],
          [
            'is_checked' => true,
            'value' => 'value 2',
          ],
        ],
      ],
    ]);
    $values = $result['params']['values'];
    expect($values)->internalType('array');
    expect($values)->count(2);
    expect($values[0])->same(['value' => 'value 1', 'is_checked' => '']);
    expect($values[1])->same(['value' => 'value 2', 'is_checked' => '1']);
  }

  function testItThrowsIfNoValuesInCheckbox() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'checkbox',
    ]);
  }

  function testItThrowsIfMoreValuesInCheckbox() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'checkbox',
      'params' => [
        'values' => [
          [
            'value' => 'value 1',
          ],
          [
            'value' => 'value 2',
          ],
        ],
      ],
    ]);
  }

  function testItThrowsIfNameValueMissingInCheckbox() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'checkbox',
      'params' => [
        'values' => [
          [
            'is_checked' => true,
          ],
        ],
      ],
    ]);
  }

  function testItSanitizeCheckbox() {
    $result = $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'checkbox',
      'params' => [
        'values' => [
          [
            'is_checked' => true,
            'value' => 'value 1',
          ],
        ],
      ],
    ]);
    $values = $result['params']['values'];
    expect($values)->internalType('array');
    expect($values)->count(1);
    expect($values[0])->same(['value' => 'value 1', 'is_checked' => '1']);
  }

  function testDateThrowsIfNoDateFormat() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => [],
    ]);
  }

  function testDateThrowsIfInvalidDateFormat() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => ['date_format' => 'invalid'],
    ]);
  }

  function testDateThrowsIfInvalidDateType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => ['date_format' => 'MM/DD/YYYY', 'date_type' => 'invalid'],
    ]);
  }

  function testSanitizeDate() {
    $result = $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => ['date_format' => 'MM/DD/YYYY', 'date_type' => 'year_month_day'],
    ]);
    expect($result['params'])->equals([
      'date_format' => 'MM/DD/YYYY',
      'date_type' => 'year_month_day',
      'label' => 'Name',
      'required' => '',
    ]);
  }
}
