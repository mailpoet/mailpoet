<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface Trigger extends Step {
  public function registerHooks(): void;
}
