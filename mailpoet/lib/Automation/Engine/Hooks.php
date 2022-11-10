<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\Step;

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
  public const AUTOMATION_STEP = 'mailpoet/automation/step';

  public const EDITOR_BEFORE_LOAD = 'mailpoet/automation/editor/before_load';

  public const AUTOMATION_BEFORE_SAVE = 'mailpoet/automation/before_save';
  public const AUTOMATION_STEP_BEFORE_SAVE = 'mailpoet/automation/step/before_save';

  public const AUTOMATION_RUN_LOG_AFTER_STEP_RUN = 'mailpoet/automation/step/after_run';

  public const AUTOMATION_TEMPLATES = 'mailpoet/automation/templates';

  public function doAutomationBeforeSave(Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_BEFORE_SAVE, $automation);
  }

  public function doAutomationStepBeforeSave(Step $step): void {
    $this->wordPress->doAction(self::AUTOMATION_STEP_BEFORE_SAVE, $step);
  }

  public function doAutomationStepByKeyBeforeSave(Step $step): void {
    $this->wordPress->doAction(self::AUTOMATION_STEP_BEFORE_SAVE . '/key=' . $step->getKey(), $step);
  }

  public function doAutomationStepAfterRun(AutomationRunLog $automationRunLog): void {
    $this->wordPress->doAction(self::AUTOMATION_RUN_LOG_AFTER_STEP_RUN, $automationRunLog);
  }
}
