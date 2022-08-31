<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Triggers;

use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Workflows\Trigger;
use MailPoet\Validator\Schema\ObjectSchema;

class EmptyTrigger implements Trigger {
  public function registerHooks(): void {
    throw new InvalidStateException();
  }

  public function getKey(): string {
    return 'core:empty';
  }

  public function getName(): string {
    return __('No trigger specified', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return new ObjectSchema();
  }
}
