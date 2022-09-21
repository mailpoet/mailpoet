<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface Trigger extends Step {
  public function registerHooks(): void;

  /**
   * Validate if the specific context of a run meets the
   * settings of a given trigger.
   */
  public function isTriggeredBy(array $args, Subject ...$subjects): bool;
}
