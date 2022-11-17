<?php declare(strict_types = 1);

namespace MailPoet\CustomFields;

use InvalidArgumentException;

class ApiDataSanitizerTest extends \MailPoetUnitTest {

  /** @var ApiDataSanitizer */
  private $sanitizer;

  public function _before() {
    $this->sanitizer = new ApiDataSanitizer();
  }

  public function testItThrowsIfNameIsMissing() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['type' => 'text']);
  }

  public function testItThrowsIfNameIsEmpty() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => '', 'type' => 'text']);
  }

  public function testItThrowsIfNameIsWrongType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => ['x'], 'type' => 'text']);
  }

  public function testItThrowsIfTypeIsMissing() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name']);
  }

  public function testItThrowsIfTypeIsEmpty() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => '']);
  }

  public function testItThrowsIfTypeIsWrongType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => ['y']]);
  }

  public function testItThrowsIfTypeIsInvalid() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'Invalid Type']);
  }

  public function testItThrowsIfParamsIsInvalidType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => 'xyz']);
  }

  public function testItReturnsArray() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result)->array();
  }

  public function testItReturnsName() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result)->hasKey('name');
    expect($result['name'])->same('Name');
  }

  public function testItReturnsType() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'Text']);
    expect($result)->hasKey('type');
    expect($result['type'])->same('text');
  }

  public function testItIgnoresUnknownProperties() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'unknown' => 'Unknown property']);
    expect($result)->hasNotKey('unknown');
  }

  public function testItReturnsParamsIfPassed() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => '1']]);
    expect($result)->hasKey('params');
  }

  public function testItReturnsCorrectRequiredForm() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => true]]);
    expect($result['params']['required'])->same('1');
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['required' => false]]);
    expect($result['params']['required'])->same('');
  }

  public function testItIgnoresUnknownParams() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['unknown' => 'Unknown property']]);
    expect($result)->hasKey('params');
    expect($result['params'])->hasNotKey('unknown');
  }

  public function testItFillsLabel() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text']);
    expect($result['params'])->hasKey('label');
    expect($result['params']['label'])->same('Name');
  }

  public function testItThrowsForInvalidValidate() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['validate' => 'unknown']]);
  }

  public function testItReturnsSanitizedValidate() {
    $result = $this->sanitizer->sanitize(['name' => 'Name', 'type' => 'text', 'params' => ['validate' => 'alphanuM']]);
    expect($result['params']['validate'])->same('alphanum');
  }

  public function testItThrowsIfNoValuesInRadio() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'radio',
    ]);
  }

  public function testItReturnsSanitizedValuesForRadio() {
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
    expect($values)->array();
    expect($values)->count(2);
    expect($values[0])->same(['value' => 'value 1', 'is_checked' => '']);
    expect($values[1])->same(['value' => 'value 2', 'is_checked' => '1']);
  }

  public function testItThrowsIfNoValuesInCheckbox() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'checkbox',
    ]);
  }

  public function testItThrowsIfMoreValuesInCheckbox() {
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

  public function testItThrowsIfNameValueMissingInCheckbox() {
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

  public function testItSanitizeCheckbox() {
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
    expect($values)->array();
    expect($values)->count(1);
    expect($values[0])->same(['value' => 'value 1', 'is_checked' => '1']);
  }

  public function testDateThrowsIfNoDateFormat() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => [],
    ]);
  }

  public function testDateThrowsIfInvalidDateFormat() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => ['date_format' => 'invalid'],
    ]);
  }

  public function testDateThrowsIfInvalidDateType() {
    $this->expectException(InvalidArgumentException::class);
    $this->sanitizer->sanitize([
      'name' => 'Name',
      'type' => 'date',
      'params' => ['date_format' => 'MM/DD/YYYY', 'date_type' => 'invalid'],
    ]);
  }

  public function testSanitizeDate() {
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
