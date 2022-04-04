<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\NotFoundException;

abstract class AbstractSubject implements Subject {
  public function getField(string $key): Field {
    if (!isset($this->getFields()[$key])) {
      throw NotFoundException::create()->withMessage(__("No field found with key '%s'.", $key));
    }
    
    return $this->getFields()[$key];
  }
}
