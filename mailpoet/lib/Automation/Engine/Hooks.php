<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;

class Hooks {
  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  public const INITIALIZE = 'mailpoet/automation/initialize';
  public const API_INITIALIZE = 'mailpoet/automation/api/initialize';
  public const STEP_RUNNER_INITIALIZE = 'mailpoet/automation/step_runner/initialize';
  public const TRIGGER = 'mailpoet/automation/trigger';
  public const WORKFLOW_STEP = 'mailpoet/automation/workflow/step';

  public const WORKFLOW_BEFORE_SAVE = 'mailpoet/automation/workflow/before_save';
  public const WORKFLOW_STEP_BEFORE_SAVE = 'mailpoet/automation/workflow/step/before_save';

  public const WORKFLOW_TEMPLATES = 'mailpoet/automation/workflow/templates';

  public function doWorkflowBeforeSave(Workflow $workflow): void {
    $this->wordPress->doAction(self::WORKFLOW_BEFORE_SAVE, $workflow);
  }

  public function doWorkflowStepBeforeSave(Step $step): void {
    $this->wordPress->doAction(self::WORKFLOW_STEP_BEFORE_SAVE, $step);
  }

  public function doWorkflowStepByKeyBeforeSave(Step $step): void {
    $this->wordPress->doAction(self::WORKFLOW_STEP_BEFORE_SAVE . '/key=' . $step->getKey(), $step);
  }
}
