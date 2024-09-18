<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Validator\Schema;

use MailPoet\EmailEditor\Validator\Schema;

// See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
class NullSchema extends Schema {
  protected $schema = [
    'type' => 'null',
  ];
}
