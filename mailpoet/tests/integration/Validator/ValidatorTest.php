<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema;
use MailPoet\Validator\ValidationException;
use MailPoet\Validator\Validator;
use MailPoetTest;
use stdClass;

class ValidatorTest extends MailPoetTest {
  public function testString(): void {
    // valid
    $this->assertValidationPassed(Builder::string(), '');
    $this->assertValidationPassed(Builder::string(), 'abc');
    $this->assertValidationPassed(Builder::string()->minLength(3), 'abc');
    $this->assertValidationPassed(Builder::string()->maxLength(3), 'abc');
    $this->assertValidationPassed(Builder::string()->pattern('^[a-z]+$'), 'abc');
    $this->assertValidationPassed(Builder::string()->formatDateTime(), '2022-03-18T12:35:27+01:00');
    $this->assertValidationPassed(Builder::string()->formatEmail(), 'test@example.com');
    $this->assertValidationPassed(Builder::string()->formatHexColor(), '#00aaff');
    $this->assertValidationPassed(Builder::string()->formatHexColor(), '#ccc');
    $this->assertValidationPassed(Builder::string()->formatIp(), '127.0.0.1');
    $this->assertValidationPassed(Builder::string()->formatIp(), '::1');
    $this->assertValidationPassed(Builder::string()->formatUri(), 'https://wordpress.org?x=y#1');
    $this->assertValidationPassed(Builder::string()->formatUri(), '/');
    $this->assertValidationPassed(Builder::string()->formatUri(), 'https://example.org/hello world', 'https://example.org/hello%20world');
    $this->assertValidationPassed(Builder::string()->formatUri(), '/test[a]=1&a=[2]', '/test%5Ba%5D=1&a=%5B2%5D');
    $this->assertValidationPassed(Builder::string()->formatUuid(), 'b2c70356-0e19-4f30-87da-1d2eadaf2d39');

    // invalid
    $this->assertValidationFailed(Builder::string()->minLength(3), 'ab', 'value must be at least 3 characters long.');
    $this->assertValidationFailed(Builder::string()->maxLength(3), 'abcd', 'value must be at most 3 characters long.');
    $this->assertValidationFailed(Builder::string()->pattern('^[a-z]+$'), 'a123', 'value does not match pattern ^[a-z]+$.');
    $this->assertValidationFailed(Builder::string()->formatDateTime(), 'abc', 'Invalid date.');
    $this->assertValidationFailed(Builder::string()->formatDateTime(), '2022-03-18', 'Invalid date.');
    $this->assertValidationFailed(Builder::string()->formatDateTime(), '12:00:00', 'Invalid date.');
    $this->assertValidationFailed(Builder::string()->formatEmail(), 'abc@', 'Invalid email address.');
    $this->assertValidationFailed(Builder::string()->formatEmail(), 'example.com', 'Invalid email address.');
    $this->assertValidationFailed(Builder::string()->formatEmail(), '@example.com', 'Invalid email address.');
    $this->assertValidationFailed(Builder::string()->formatHexColor(), '00aaff', 'Invalid hex color.');
    $this->assertValidationFailed(Builder::string()->formatHexColor(), 'ccc', 'Invalid hex color.');
    $this->assertValidationFailed(Builder::string()->formatHexColor(), '#00xxzz', 'Invalid hex color.');
    $this->assertValidationFailed(Builder::string()->formatIp(), '127.0.0.', 'value is not a valid IP address.');
    $this->assertValidationFailed(Builder::string()->formatIp(), '127.0.0', 'value is not a valid IP address.');
    $this->assertValidationFailed(Builder::string()->formatIp(), ':::1', 'value is not a valid IP address.');
    $this->assertValidationFailed(Builder::string()->formatIp(), ':', 'value is not a valid IP address.');
    $this->assertValidationFailed(Builder::string()->formatUuid(), 'b2c703560e194f3087da1d2eadaf2d39', 'value is not a valid UUID.');
    $this->assertValidationFailed(Builder::string(), 1, 'value is not of type string.');
    $this->assertValidationFailed(Builder::string(), null, 'value is not of type string.');
    $this->assertValidationFailed(Builder::string(), true, 'value is not of type string.');
    $this->assertValidationFailed(Builder::string(), false, 'value is not of type string.');
    $this->assertValidationFailed(Builder::string(), [], 'value is not of type string.');
    $this->assertValidationFailed(Builder::string(), new stdClass(), 'value is not of type string.');
  }

  public function testNumber(): void {
    // valid
    $this->assertValidationPassed(Builder::number(), 5, 5.0);
    $this->assertValidationPassed(Builder::number(), 0.123);
    $this->assertValidationPassed(Builder::number(), 1e3);
    $this->assertValidationPassed(Builder::number(), -5, -5.0);
    $this->assertValidationPassed(Builder::number(), -0.123);
    $this->assertValidationPassed(Builder::number(), -1e3);
    $this->assertValidationPassed(Builder::number(), 0, 0.0);
    $this->assertValidationPassed(Builder::number(), -0, 0.0);

    // invalid
    $this->assertValidationFailed(Builder::number(), '0', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), '5', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), '5.0', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), '-5', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), '1e3', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), '', 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), null, 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), true, 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), false, 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), [], 'value is not of type number.');
    $this->assertValidationFailed(Builder::number(), new stdClass(), 'value is not of type number.');
  }

  public function testInteger(): void {
    // valid
    $this->assertValidationPassed(Builder::integer(), 5);
    $this->assertValidationPassed(Builder::integer(), -5);
    $this->assertValidationPassed(Builder::integer(), 0);
    $this->assertValidationPassed(Builder::integer(), -0);

    // invalid
    $this->assertValidationFailed(Builder::integer(), '0', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '5', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '5.0', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '-5', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '1e3', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '5', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '5.0', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), 5.0, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), 5.1, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), 1e3, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), '', 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), null, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), true, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), false, 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), [], 'value is not of type integer.');
    $this->assertValidationFailed(Builder::integer(), new stdClass(), 'value is not of type integer.');
  }

  public function testBoolean(): void {
    // valid
    $this->assertValidationPassed(Builder::boolean(), true);
    $this->assertValidationPassed(Builder::boolean(), false);

    // invalid
    $this->assertValidationFailed(Builder::boolean(), 'true', 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), 'false', 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), '0', 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), '1', 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), '', 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), 1, 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), null, 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), [], 'value is not of type boolean.');
    $this->assertValidationFailed(Builder::boolean(), new stdClass(), 'value is not of type boolean.');
  }

  public function testNull(): void {
    // valid
    $this->assertValidationPassed(Builder::null(), null);

    // invalid
    $this->assertValidationFailed(Builder::null(), '', 'value is not of type null.');
    $this->assertValidationFailed(Builder::null(), 'null', 'value is not of type null.');
    $this->assertValidationFailed(Builder::null(), 0, 'value is not of type null.');
    $this->assertValidationFailed(Builder::null(), [], 'value is not of type null.');
    $this->assertValidationFailed(Builder::null(), new stdClass(), 'value is not of type null.');
  }

  public function testArray(): void {
    // valid
    $this->assertValidationPassed(Builder::array(Builder::number()), []);
    $this->assertValidationPassed(Builder::array(Builder::number()), [1, 2, 3], [1.0, 2.0, 3.0]);
    $this->assertValidationPassed(Builder::array(Builder::number()), [-1e5, -0.123, 0.0, 1e3, 5.1]);
    $this->assertValidationPassed(Builder::array(Builder::number()), [1e3]);
    $this->assertValidationPassed(Builder::array(Builder::number())->minItems(3)->maxItems(3), [1.0, 2.0, 3.0]);
    $this->assertValidationPassed(Builder::array(Builder::number())->uniqueItems(), [1, 2, 3], [1.0, 2.0, 3.0]);

    // invalid
    $this->assertValidationFailed(Builder::array(), '', 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(), 'null', 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(), 0, 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(), new stdClass(), 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(), 'a,b', 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(), ['x' => 'x', 'y' => 'y'], 'value is not of type array.');
    $this->assertValidationFailed(Builder::array(Builder::number()), [1, '2'], 'value[1] is not of type number.');
    $this->assertValidationFailed(Builder::array(Builder::number()), [1, null], 'value[1] is not of type number.');
    $this->assertValidationFailed(Builder::array(Builder::number()), [1, false], 'value[1] is not of type number.');
    $this->assertValidationFailed(Builder::array(Builder::number())->minItems(3), [1, 2], 'value must contain at least 3 items.');
    $this->assertValidationFailed(Builder::array(Builder::number())->maxItems(1), [1, 2], 'value must contain at most 1 item.');
    $this->assertValidationFailed(Builder::array(Builder::number())->uniqueItems(), [1, 2, 1], 'value has duplicate items.');
    $this->assertValidationFailed(Builder::array(Builder::number())->uniqueItems(), [1.0, 1], 'value has duplicate items.');
    $this->assertValidationFailed(Builder::array(Builder::number())->uniqueItems(), [0, -0], 'value has duplicate items.');
  }

  public function testObject(): void {
    // valid - basics
    $this->assertValidationPassed(Builder::object(['n' => Builder::number()]), []);
    $this->assertValidationPassed(Builder::object(['n' => Builder::number()]), new stdClass(), []);
    $this->assertValidationPassed(Builder::object(['n' => Builder::integer()]), ['i' => 123]);
    $this->assertValidationPassed(Builder::object(['n' => Builder::integer()]), ['i' => 123, 's' => 'abc']);

    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer(), 's' => Builder::string()]),
      ['i' => 5, 's' => 'abc']
    );

    // valid - required, min-properties, max-properties
    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer()->required(), 's' => Builder::string()]),
      ['i' => 5]
    );

    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer(), 's' => Builder::string()])->minProperties(1),
      ['s' => 'abc']
    );

    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer(), 's' => Builder::string()])->minProperties(1),
      ['s' => 'abc']
    );

    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer(), 's' => Builder::string()])->maxProperties(1),
      ['i' => 5]
    );

    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer(), 's' => Builder::string()])->maxProperties(1),
      ['s' => 'abc']
    );

    // valid - no additional properties
    $this->assertValidationPassed(
      Builder::object(['i' => Builder::integer()])->disableAdditionalProperties(),
      ['i' => 5]
    );

    // valid - additional properties
    $this->assertValidationPassed(
      Builder::object()->additionalProperties(Builder::integer()),
      ['a' => 1, 'b' => 2, 'c' => 3]
    );

    // valid - pattern properties
    $this->assertValidationPassed(
      Builder::object()->patternProperties(['^i_' => Builder::integer(), '^s_' => Builder::string()]),
      ['i_1' => 1, 'i_2' => 2, 's_1' => 'abc', 's_2' => '', 's_3' => 'xyz']
    );

    // invalid - basics
    $this->assertValidationFailed(
      Builder::object(['n' => Builder::number(), 's' => Builder::string()]),
      ['n' => '1', 's' => 'abc'],
      'value[n] is not of type number.'
    );
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), ['abc'], 'value is not of type object.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), [1, 2, 3], 'value is not of type object.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), '', 'value is not of type object.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), null, 'value is not of type object.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), true, 'value is not of type object.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()]), false, 'value is not of type object.');

    // invalid - required, min-properties, max-properties
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()->required()]), [], 'n is a required property of value.');
    $this->assertValidationFailed(Builder::object(['n' => Builder::number()->required()]), new stdClass(), 'n is a required property of value.');

    $this->assertValidationFailed(
      Builder::object(['i' => Builder::integer()->required(), 's' => Builder::string()]),
      ['s' => 'abc'],
      'i is a required property of value.'
    );

    $this->assertValidationFailed(
      Builder::object()->minProperties(1),
      [],
      'value must contain at least 1 property.'
    );

    $this->assertValidationFailed(
      Builder::object()->maxProperties(1),
      ['i' => 5, 's' => 'abc'],
      'value must contain at most 1 property.'
    );

    // invalid - no additional properties
    $this->assertValidationFailed(
      Builder::object(['i' => Builder::integer()])->disableAdditionalProperties(),
      ['i' => 5, 's' => 'abc'],
      's is not a valid property of Object.'
    );

    // invalid - additional properties
    $this->assertValidationFailed(
      Builder::object()->additionalProperties(Builder::integer()),
      ['a' => 1, 'b' => 'abc', 'c' => 3],
      'value[b] is not of type integer.'
    );

    // invalid - pattern properties
    $this->assertValidationFailed(
      Builder::object()->patternProperties(['^i_' => Builder::integer(), '^s_' => Builder::string()]),
      ['i_1' => 'abc', 's_1' => 'abc'],
      'value[i_1] is not of type integer.'
    );

    $this->assertValidationFailed(
      Builder::object()->patternProperties(['^i_' => Builder::integer(), '^s_' => Builder::string()]),
      ['i_1' => 5, 's_1' => 5],
      'value[s_1] is not of type string.'
    );
  }

  public function testOneOf(): void {
    // valid
    $this->assertValidationPassed(Builder::oneOf([Builder::string()]), 'abc');
    $this->assertValidationPassed(Builder::oneOf([Builder::number(), Builder::string(), Builder::integer()]), '123');
    $this->assertValidationPassed(Builder::oneOf([Builder::boolean(), Builder::string(), Builder::integer()]), '1');
    $this->assertValidationPassed(Builder::oneOf([Builder::integer(), Builder::number()]), 5.0);
    $this->assertValidationPassed(Builder::oneOf([Builder::boolean(), Builder::number()]), true);
    $this->assertValidationPassed(Builder::oneOf([Builder::array(), Builder::object()]), ['abc']);
    $this->assertValidationPassed(Builder::oneOf([Builder::array(), Builder::object()]), new stdClass(), []);
    $this->assertValidationPassed(Builder::oneOf([Builder::integer(), Builder::null()]), null);

    // valid - nested object has different property type
    $this->assertValidationPassed(
      Builder::oneOf([
        Builder::object([
          'n' => Builder::number(),
          's' => Builder::string(),
          'o' => Builder::object([
            'test' => Builder::integer(),
          ]),
        ]),
        Builder::object([
          'n' => Builder::number(),
          's' => Builder::string(),
          'o' => Builder::object([
            'test' => Builder::boolean(),
          ]),
        ]),
      ]),
      ['n' => 5.2, 's' => 'abc', 'o' => ['test' => false]]
    );

    // valid - nested arrays have different item types
    $this->assertValidationPassed(
      Builder::oneOf([
        Builder::object([
          'n' => Builder::number(),
          's' => Builder::string(),
          'o' => Builder::object([
            'test' => Builder::array(Builder::string()->nullable()),
          ]),
        ]),
        Builder::object([
          'n' => Builder::number(),
          's' => Builder::string(),
          'o' => Builder::object([
            'test' => Builder::array(Builder::boolean()),
          ]),
        ]),
      ]),
      ['n' => 5.2, 's' => 'abc', 'o' => ['test' => ['a', 'b', 'c', '', null]]]
    );

    // invalid
    $this->assertValidationFailed(Builder::oneOf([Builder::number(), Builder::integer()]), 5, 'value matches more than one of the expected formats.');
    $this->assertValidationFailed(Builder::oneOf([Builder::array(), Builder::object()]), [], 'value matches more than one of the expected formats.');
    $this->assertValidationFailed(Builder::oneOf([]), null, 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), '', 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), 'abc', 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), [], 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), true, 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), false, 'value is not a valid ');
    $this->assertValidationFailed(Builder::oneOf([]), 0, 'value is not a valid ');

    // invalid (integer and number both match, error on positions 1, 2)
    $e = $this->assertValidationFailed(
      Builder::oneOf([Builder::string(), Builder::integer(), Builder::number()]),
      5,
      'value matches more than one of the expected formats.'
    );
    $this->assertSame(['rest_one_of_multiple_matches' => ['positions' => [1, 2]]], $e->getWpError()->error_data);

    // invalid (string used twice, error on positions 1, 3)
    $e = $this->assertValidationFailed(
      Builder::oneOf([Builder::boolean(), Builder::string(), Builder::number(), Builder::string()]),
      '5',
      'value matches more than one of the expected formats.'
    );
    $this->assertSame(['rest_one_of_multiple_matches' => ['positions' => [1, 3]]], $e->getWpError()->error_data);
  }

  public function testAnyOf(): void {
    // valid
    $this->assertValidationPassed(Builder::anyOf([Builder::string()]), 'abc');
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::string(), Builder::integer()]), '123');
    $this->assertValidationPassed(Builder::anyOf([Builder::boolean(), Builder::string(), Builder::integer()]), '1');
    $this->assertValidationPassed(Builder::anyOf([Builder::integer(), Builder::number()]), 5.0);
    $this->assertValidationPassed(Builder::anyOf([Builder::boolean(), Builder::number()]), true);
    $this->assertValidationPassed(Builder::anyOf([Builder::array(), Builder::object()]), ['abc']);
    $this->assertValidationPassed(Builder::anyOf([Builder::array(), Builder::object()]), new stdClass(), []);
    $this->assertValidationPassed(Builder::anyOf([Builder::integer(), Builder::null()]), null);

    // valid - int (can be coerced to float, order of integer/number is important)
    $this->assertValidationPassed(Builder::anyOf([Builder::integer(), Builder::number()]), 5);
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::integer()]), 5, 5.0);

    // valid - float (can't be coerced to int, order of integer/number not important)
    $this->assertValidationPassed(Builder::anyOf([Builder::integer(), Builder::number()]), 5.0);
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::integer()]), 5.0);

    // invalid
    $this->assertValidationFailed(Builder::anyOf([Builder::number(), Builder::integer()]), '5', 'value does not match any of the expected formats.');
    $this->assertValidationFailed(Builder::anyOf([]), null, 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), '', 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), 'abc', 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), [], 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), true, 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), false, 'value is not a valid ');
    $this->assertValidationFailed(Builder::anyOf([]), 0, 'value is not a valid ');
  }

  public function testNullable(): void {
    // valid
    $this->assertValidationPassed(Builder::string()->nullable(), null);
    $this->assertValidationPassed(Builder::number()->nullable(), null);
    $this->assertValidationPassed(Builder::integer()->nullable(), null);
    $this->assertValidationPassed(Builder::boolean()->nullable(), null);
    $this->assertValidationPassed(Builder::null()->nullable(), null);
    $this->assertValidationPassed(Builder::null()->nonNullable(), null);
    $this->assertValidationPassed(Builder::array()->nullable(), null);
    $this->assertValidationPassed(Builder::object()->nullable(), null);
    $this->assertValidationPassed(Builder::oneOf([])->nullable(), null);
    $this->assertValidationPassed(Builder::anyOf([])->nullable(), null);

    // valid - oneOf/anyOf with schemas and values
    $this->assertValidationPassed(Builder::oneOf([Builder::number(), Builder::string()])->nullable(), null);
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::string()])->nullable(), null);
    $this->assertValidationPassed(Builder::oneOf([Builder::number(), Builder::string()])->nullable(), 5.0);
    $this->assertValidationPassed(Builder::oneOf([Builder::number(), Builder::string()])->nullable(), 'abc');
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::string()])->nullable(), 5.0);
    $this->assertValidationPassed(Builder::anyOf([Builder::number(), Builder::string()])->nullable(), 'abc');

    // invalid
    $this->assertValidationFailed(Builder::number()->nullable(), '', 'value is not of type number,null.');
    $this->assertValidationFailed(Builder::number()->nullable(), '0', 'value is not of type number,null.');
    $this->assertValidationFailed(Builder::number()->nullable(), false, 'value is not of type number,null.');
    $this->assertValidationFailed(Builder::number()->nullable(), [], 'value is not of type number,null.');
    $this->assertValidationFailed(Builder::string()->nullable(), 0, 'value is not of type string,null.');
    $this->assertValidationFailed(Builder::boolean()->nullable(), 0, 'value is not of type boolean,null.');
  }

  public function testComplex(): void {
    $schema = Builder::object()
      ->title('User')
      ->description('User schema definition')
      ->field('name', 'user')
      ->field('version', 1)
      ->properties([
        'id' => Builder::string()->required()->formatUuid(),
        'created_at' => Builder::string()->required()->formatDateTime(),
        'username' => Builder::string()->required()->minLength(2)->maxLength(30)->pattern('^[a-z0-9]+$'),
        'password' => Builder::string()->required()->minLength(8)->maxLength(1024),
        'email' => Builder::string()->required()->formatEmail(),
        'ip' => Builder::string()->required()->formatIp(),
        'refresh_interval' => Builder::integer()->required()->multipleOf(3600),
        'subscribed' => Builder::boolean()->default(false),

        // nested object
        'profile' => Builder::object([
          'url' => Builder::string()->required()->formatUri(),
          'photo_url' => Builder::string()->required()->nullable(),
          'color' => Builder::string()->required()->formatHexColor(),
          'age' => Builder::integer()->required(),
          'rating' => Builder::integer()->required()->minimum(0)->maximum(5),
          'score' => Builder::number()->required()->exclusiveMinimum(0)->maximum(100),
          'distance' => Builder::number()->required(),
        ]),

        // array of unique objects
        'preferences' => Builder::array(
          Builder::object([
            'key' => Builder::string()->required(),
            'value' => Builder::anyOf([Builder::string(), Builder::integer(), Builder::number()])->required(),
            'meta' => Builder::string(),
          ])
        )->uniqueItems(),

        // pattern properties
        'properties' => Builder::object()->patternProperties([
          '^number_' => Builder::anyOf([Builder::integer(), Builder::number()]),
          '^string_' => Builder::string(),
          '^bool_' => Builder::boolean(),
        ]),

        // oneOf
        'linked_accounts' => Builder::array(
          Builder::oneOf([
            Builder::object([
              'apple_id' => Builder::string()->required(),
            ]),
            Builder::object([
              'facebook_id' => Builder::string()->required(),
            ]),
            Builder::object([
              'google_id' => Builder::string()->required(),
            ]),
          ])
        ),

        // additional properties, anyOf
        'attributes' => Builder::object()->additionalProperties(
          Builder::anyOf([
            Builder::string(),
            Builder::integer(),
            Builder::boolean(),
            Builder::number(),
            Builder::null(),
          ])
        ),
      ]);

    $this->assertSame(
      [
        'type' => 'object',
        'title' => 'User',
        'description' => 'User schema definition',
        'name' => 'user',
        'version' => 1,
        'properties' => [
          'id' => [
            'type' => 'string',
            'required' => true,
            'format' => 'uuid',
          ],
          'created_at' => [
            'type' => 'string',
            'required' => true,
            'format' => 'date-time',
          ],
          'username' => [
            'type' => 'string',
            'required' => true,
            'minLength' => 2,
            'maxLength' => 30,
            'pattern' => '^[a-z0-9]+$',
          ],
          'password' => [
            'type' => 'string',
            'required' => true,
            'minLength' => 8,
            'maxLength' => 1024,
          ],
          'email' => [
            'type' => 'string',
            'required' => true,
            'format' => 'email',
          ],
          'ip' => [
            'type' => 'string',
            'required' => true,
            'format' => 'ip',
          ],
          'refresh_interval' => [
            'type' => 'integer',
            'required' => true,
            'multipleOf' => 3600,
          ],
          'subscribed' => [
            'type' => 'boolean',
            'default' => false,
          ],
          'profile' => [
            'type' => 'object',
            'properties' => [
              'url' => [
                'type' => 'string',
                'required' => true,
                'format' => 'uri',
              ],
              'photo_url' => [
                'type' => ['string', 'null'],
                'required' => true,
              ],
              'color' => [
                'type' => 'string',
                'required' => true,
                'format' => 'hex-color',
              ],
              'age' => [
                'type' => 'integer',
                'required' => true,
              ],
              'rating' => [
                'type' => 'integer',
                'required' => true,
                'minimum' => 0,
                'maximum' => 5,
              ],
              'score' => [
                'type' => 'number',
                'required' => true,
                'minimum' => 0.0,
                'exclusiveMinimum' => true,
                'maximum' => 100.0,
              ],
              'distance' => [
                'type' => 'number',
                'required' => true,
              ],
            ],
          ],
          'preferences' => [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'key' => [
                  'type' => 'string',
                  'required' => true,
                ],
                'value' => [
                  'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'integer'],
                    ['type' => 'number'],
                  ],
                  'required' => true,
                ],
                'meta' => [
                  'type' => 'string',
                ],
              ],
            ],
            'uniqueItems' => true,
          ],
          'properties' => [
            'type' => 'object',
            'patternProperties' => [
              '^number_' => [
                'anyOf' => [
                  ['type' => 'integer'],
                  ['type' => 'number'],
                ],
              ],
              '^string_' => ['type' => 'string'],
              '^bool_' => ['type' => 'boolean'],
            ],
          ],
          'linked_accounts' => [
            'type' => 'array',
            'items' => [
              'oneOf' => [
                [
                  'type' => 'object',
                  'properties' => [
                    'apple_id' => [
                      'type' => 'string',
                      'required' => true,
                    ],
                  ],
                ],
                [
                  'type' => 'object',
                  'properties' => [
                    'facebook_id' => [
                      'type' => 'string',
                      'required' => true,
                    ],
                  ],
                ],
                [
                  'type' => 'object',
                  'properties' => [
                    'google_id' => [
                      'type' => 'string',
                      'required' => true,
                    ],
                  ],
                ],
              ],
            ],
          ],
          'attributes' => [
            'type' => 'object',
            'additionalProperties' => [
              'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
                ['type' => 'boolean'],
                ['type' => 'number'],
                ['type' => 'null'],
              ],
            ],
          ],
        ],
      ],
      $schema->toArray()
    );

    $this->assertSame(
      $schema->toString(),
      '{"type":"object","title":"User","description":"User schema definition","name":"user","version":1,"properties":{"id":{"type":"string","required":true,"format":"uuid"},"created_at":{"type":"string","required":true,"format":"date-time"},"username":{"type":"string","required":true,"minLength":2,"maxLength":30,"pattern":"^[a-z0-9]+$"},"password":{"type":"string","required":true,"minLength":8,"maxLength":1024},"email":{"type":"string","required":true,"format":"email"},"ip":{"type":"string","required":true,"format":"ip"},"refresh_interval":{"type":"integer","required":true,"multipleOf":3600},"subscribed":{"type":"boolean","default":false},"profile":{"type":"object","properties":{"url":{"type":"string","required":true,"format":"uri"},"photo_url":{"type":["string","null"],"required":true},"color":{"type":"string","required":true,"format":"hex-color"},"age":{"type":"integer","required":true},"rating":{"type":"integer","required":true,"minimum":0,"maximum":5},"score":{"type":"number","required":true,"minimum":0.0,"exclusiveMinimum":true,"maximum":100.0},"distance":{"type":"number","required":true}}},"preferences":{"type":"array","items":{"type":"object","properties":{"key":{"type":"string","required":true},"value":{"anyOf":[{"type":"string"},{"type":"integer"},{"type":"number"}],"required":true},"meta":{"type":"string"}}},"uniqueItems":true},"properties":{"type":"object","patternProperties":{"^number_":{"anyOf":[{"type":"integer"},{"type":"number"}]},"^string_":{"type":"string"},"^bool_":{"type":"boolean"}}},"linked_accounts":{"type":"array","items":{"oneOf":[{"type":"object","properties":{"apple_id":{"type":"string","required":true}}},{"type":"object","properties":{"facebook_id":{"type":"string","required":true}}},{"type":"object","properties":{"google_id":{"type":"string","required":true}}}]}},"attributes":{"type":"object","additionalProperties":{"anyOf":[{"type":"string"},{"type":"integer"},{"type":"boolean"},{"type":"number"},{"type":"null"}]}}}}'
    );

    $this->assertValidationPassed(
      $schema,
      [
        'id' => '4c30b777-9067-4a7b-b498-e295eabbf6ef',
        'created_at' => '2022-03-23T15:21:37',
        'username' => 'mailpoet',
        'password' => '4f38f3eb301923fd38f3',
        'email' => 'mailpoet@example.com',
        'ip' => '145.8.23.191',
        'refresh_interval' => 7200,
        'subscribed' => false,
        'profile' => [
          'url' => 'https://example.com',
          'photo_url' => null,
          'color' => '#a5a5a5',
          'age' => 38,
          'rating' => 4,
          'score' => 87.372,
          'distance' => 1023.28,
        ],
        'preferences' => [
          ['key' => 'theme', 'value' => 'dark'],
          ['key' => 'screen_with', 'value' => 1170, 'meta' => 'ios/safari'],
          ['key' => 'screen_with', 'value' => 3072, 'meta' => 'macos/chrome'],
        ],
        'properties' => [
          'bool_tracking_enabled' => true,
          'number_tracking_page_views' => 12,
          'string_tracking_id' => 'az0x8kw25as',
        ],
        'linked_accounts' => [
          ['apple_id' => 'f794613bd965'],
          ['google_id' => 'f794613bd965'],
        ],
        'attributes' => [
          'browser' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.82 Safari/537.36',
          'language' => 'en_US',
          'timezone' => 'Europe/Prague',
          'login_attempts' => 2,
          'is_admin' => false,
          'referrer' => null,
          'ping' => 173,
          'risk_score' => 0.10378,
        ],
      ]
    );
  }

  private function assertValidationPassed(Schema $schema, $value, $sanitizedValue = null): void {
    $validator = $this->diContainer->get(Validator::class);
    $sanitized = $validator->validate($schema, $value);
    $this->assertSame($sanitizedValue ?? $value, $sanitized);
  }

  private function assertValidationFailed(Schema $schema, $value, string $message): ValidationException {
    try {
      $validator = $this->diContainer->get(Validator::class);
      $validator->validate($schema, $value);
    } catch (ValidationException $e) {
      $this->assertSame($message, $e->getMessage());
      return $e;
    }
    $class = ValidationException::class;
    $this->fail("Exception '$class' with message '$message' was not thrown.");
  }
}
