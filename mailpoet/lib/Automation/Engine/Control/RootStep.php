<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Integration\Step;
use MailPoet\Validator\Schema\ObjectSchema;

class RootStep implements Step {
  public function getKey(): string {
    return 'core:root';
  }

  public function getName(): string {
    return __('Root step', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return new ObjectSchema();
  }

  public function getSubjectKeys(): array {
    return [];
  }
}
