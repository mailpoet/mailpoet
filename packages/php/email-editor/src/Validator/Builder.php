<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator;

use MailPoet\EmailEditor\Validator\Schema\AnyOfSchema;
use MailPoet\EmailEditor\Validator\Schema\ArraySchema;
use MailPoet\EmailEditor\Validator\Schema\BooleanSchema;
use MailPoet\EmailEditor\Validator\Schema\IntegerSchema;
use MailPoet\EmailEditor\Validator\Schema\NullSchema;
use MailPoet\EmailEditor\Validator\Schema\NumberSchema;
use MailPoet\EmailEditor\Validator\Schema\ObjectSchema;
use MailPoet\EmailEditor\Validator\Schema\OneOfSchema;
use MailPoet\EmailEditor\Validator\Schema\StringSchema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
class Builder {
  public static function string(): StringSchema {
    return new StringSchema();
  }

  public static function number(): NumberSchema {
    return new NumberSchema();
  }

  public static function integer(): IntegerSchema {
    return new IntegerSchema();
  }

  public static function boolean(): BooleanSchema {
    return new BooleanSchema();
  }

  public static function null(): NullSchema {
    return new NullSchema();
  }

  public static function array(Schema $items = null): ArraySchema {
    $array = new ArraySchema();
    return $items ? $array->items($items) : $array;
  }

  /** @param array<string, Schema>|null $properties */
  public static function object(array $properties = null): ObjectSchema {
    $object = new ObjectSchema();
    return $properties === null ? $object : $object->properties($properties);
  }

  /** @param Schema[] $schemas */
  public static function oneOf(array $schemas): OneOfSchema {
    return new OneOfSchema($schemas);
  }

  /** @param Schema[] $schemas */
  public static function anyOf(array $schemas): AnyOfSchema {
    return new AnyOfSchema($schemas);
  }
}
