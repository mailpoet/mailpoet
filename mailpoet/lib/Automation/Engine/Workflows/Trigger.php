<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\WorkflowRun;

interface Trigger extends Step {
  public function registerHooks(): void;

  /**
   * Validate if the specific context of a run meets the
   * settings of a given trigger.
   *
   * @param WorkflowRun $workflowRun
   * @return bool
   */
  public function isTriggeredBy(WorkflowRun $workflowRun): bool;
}
