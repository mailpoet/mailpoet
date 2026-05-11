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
  public const TRIGGER = 'mailpoet/automation/trigger';
  public const AUTOMATION_STEP = 'mailpoet/automation/step';

  public const EDITOR_BEFORE_LOAD = 'mailpoet/automation/editor/before_load';

  public const AUTOMATION_BEFORE_SAVE = 'mailpoet/automation/before_save';
  public const AUTOMATION_STEP_BEFORE_SAVE = 'mailpoet/automation/step/before_save';
  public const AUTOMATION_AFTER_SAVE = 'mailpoet/automation/after_save';
  public const AUTOMATION_AFTER_CREATE = 'mailpoet/automation/after_create';
  public const AUTOMATION_AFTER_CREATE_FROM_TEMPLATE = 'mailpoet/automation/after_create_from_template';
  public const AUTOMATION_AFTER_UPDATE = 'mailpoet/automation/after_update';
  public const AUTOMATION_AFTER_DELETE = 'mailpoet/automation/after_delete';
  public const AUTOMATION_AFTER_DUPLICATE = 'mailpoet/automation/after_duplicate';

  public const AUTOMATION_STEP_LOG_AFTER_RUN = 'mailpoet/automation/step/log_after_run';

  public const AUTOMATION_RUN_CREATE = 'mailpoet/automation/run/create';

  public function doAutomationBeforeSave(Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_BEFORE_SAVE, $automation);
  }

  public function doAutomationAfterSave(Automation $automation, ?Automation $previousAutomation = null): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_SAVE, $automation, $previousAutomation);
  }

  public function doAutomationAfterCreate(Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_CREATE, $automation);
    $this->doAutomationAfterSave($automation);
  }

  public function doAutomationAfterCreateFromTemplate(Automation $automation, string $templateSlug): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_CREATE_FROM_TEMPLATE, $automation, $templateSlug);
    $this->doAutomationAfterCreate($automation);
  }

  public function doAutomationAfterUpdate(Automation $automation, Automation $previousAutomation): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_UPDATE, $automation, $previousAutomation);
    $this->doAutomationAfterSave($automation, $previousAutomation);
  }

  public function doAutomationAfterDelete(Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_DELETE, $automation);
  }

  public function doAutomationAfterDuplicate(Automation $automation, Automation $sourceAutomation): void {
    $this->wordPress->doAction(self::AUTOMATION_AFTER_DUPLICATE, $automation, $sourceAutomation);
    $this->doAutomationAfterCreate($automation);
  }

  public function doAutomationStepBeforeSave(Step $step, Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_STEP_BEFORE_SAVE, $step, $automation);
  }

  public function doAutomationStepByKeyBeforeSave(Step $step, Automation $automation): void {
    $this->wordPress->doAction(self::AUTOMATION_STEP_BEFORE_SAVE . '/key=' . $step->getKey(), $step, $automation);
  }

  public function doAutomationStepAfterRun(AutomationRunLog $automationRunLog): void {
    $this->wordPress->doAction(self::AUTOMATION_STEP_LOG_AFTER_RUN, $automationRunLog);
  }
}
