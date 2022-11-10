<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation;

use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ArraySchema;
use MailPoet\Validator\Schema\ObjectSchema;

class AutomationSchema {
  public static function getSchema(): ObjectSchema {
    return Builder::object([
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->minLength(1)->required(),
      'status' => Builder::string()->required(),
      'steps' => self::getStepsSchema()->required(),
    ]);
  }

  public static function getStepsSchema(): ObjectSchema {
    return Builder::object()
      ->properties(['root' => self::getRootStepSchema()->required()])
      ->additionalProperties(self::getStepSchema());
  }

  public static function getStepSchema(): ObjectSchema {
    return Builder::object([
      'id' => Builder::string()->required(),
      'type' => Builder::string()->required(),
      'key' => Builder::string()->required(),
      'args' => Builder::object()->required(),
      'next_steps' => self::getNextStepsSchema()->required(),
    ]);
  }

  public static function getRootStepSchema(): ObjectSchema {
    return Builder::object([
      'id' => Builder::string()->pattern('^root$'),
      'type' => Builder::string()->pattern('^root$'),
      'key' => Builder::string()->pattern('^core:root$'),
      'args' => Builder::object()->disableAdditionalProperties(),
      'next_steps' => self::getNextStepsSchema()->required(),
    ]);
  }

  public static function getNextStepsSchema(): ArraySchema {
    return Builder::array(
      Builder::object([
        'id' => Builder::string()->required(),
      ])
    )->maxItems(1);
  }
}
