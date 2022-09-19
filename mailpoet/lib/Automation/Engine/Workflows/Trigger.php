<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\SubjectEntry;

interface Trigger extends Step {
  public function registerHooks(): void;

  /**
   * Validate if the specific context of a run meets the
   * settings of a given trigger.
   *
   * @param SubjectEntry[] $subjectEntries
   */
  public function isTriggeredBy(array $args, array $subjectEntries): bool;
}
