<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator;

use MailPoet\EmailEditor\Validator\Schema\Any_Of_Schema;
use MailPoet\EmailEditor\Validator\Schema\Array_Schema;
use MailPoet\EmailEditor\Validator\Schema\Boolean_Schema;
use MailPoet\EmailEditor\Validator\Schema\Integer_Schema;
use MailPoet\EmailEditor\Validator\Schema\Null_Schema;
use MailPoet\EmailEditor\Validator\Schema\Number_Schema;
use MailPoet\EmailEditor\Validator\Schema\Object_Schema;
use MailPoet\EmailEditor\Validator\Schema\One_Of_Schema;
use MailPoet\EmailEditor\Validator\Schema\String_Schema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
class Builder {
  public static function string(): String_Schema {
    return new String_Schema();
  }

  public static function number(): Number_Schema {
    return new Number_Schema();
  }

  public static function integer(): Integer_Schema {
    return new Integer_Schema();
  }

  public static function boolean(): Boolean_Schema {
    return new Boolean_Schema();
  }

  public static function null(): Null_Schema {
    return new Null_Schema();
  }

  public static function array(Schema $items = null): Array_Schema {
    $array = new Array_Schema();
    return $items ? $array->items($items) : $array;
  }

  /** @param array<string, Schema>|null $properties */
  public static function object(array $properties = null): Object_Schema {
    $object = new Object_Schema();
    return $properties === null ? $object : $object->properties($properties);
  }

  /** @param Schema[] $schemas */
  public static function oneOf(array $schemas): One_Of_Schema {
    return new One_Of_Schema($schemas);
  }

  /** @param Schema[] $schemas */
  public static function anyOf(array $schemas): Any_Of_Schema {
    return new Any_Of_Schema($schemas);
  }
}
