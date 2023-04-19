<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\WP\Functions;

class TriggerHandler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var SubjectLoader */
  private $subjectLoader;

  /** @var WordPress */
  private $wordPress;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var Functions  */
  private $wp;

  /** @var SubjectTransformerHandler */
  private $subjectTransformerHandler;

  public function __construct(
    ActionScheduler $actionScheduler,
    SubjectLoader $subjectLoader,
    WordPress $wordPress,
    AutomationStorage $automationStorage,
    AutomationRunStorage $automationRunStorage,
    Functions $wp,
    SubjectTransformerHandler $subjectTransformerHandler
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->wordPress = $wordPress;
    $this->automationStorage = $automationStorage;
    $this->automationRunStorage = $automationRunStorage;
    $this->subjectLoader = $subjectLoader;
    $this->wp = $wp;
    $this->subjectTransformerHandler = $subjectTransformerHandler;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::TRIGGER, [$this, 'processTrigger'], 10, 2);
  }

  /** @param Subject[] $subjects */
  public function processTrigger(Trigger $trigger, array $subjects): void {
    $automations = $this->automationStorage->getActiveAutomationsByTrigger($trigger);
    if (!$automations) {
      return;
    }

    $subjects = $this->subjectTransformerHandler->getAllSubjects($subjects);
    foreach ($automations as $automation) {
      $step = $automation->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::automationTriggerNotFound($automation->getId(), $trigger->getKey());
      }

      // ensure subjects are registered and loadable
      $subjectEntries = $this->subjectLoader->getSubjectsEntries($subjects);
      foreach ($subjectEntries as $entry) {
        $entry->getPayload();
      }

      $automationRun = new AutomationRun($automation->getId(), $automation->getVersionId(), $trigger->getKey(), $subjects);
      $stepRunArgs = new StepRunArgs($automation, $automationRun, $step, $subjectEntries);
      $createAutomationRun = $trigger->isTriggeredBy($stepRunArgs);
      $createAutomationRun = $this->wp->applyFilters(
        Hooks::AUTOMATION_RUN_CREATE,
        $createAutomationRun,
        $stepRunArgs
      );
      if (!$createAutomationRun) {
        continue;
      }

      $automationRunId = $this->automationRunStorage->createAutomationRun($automationRun);
      $nextStep = $step->getNextSteps()[0] ?? null;
      $this->actionScheduler->enqueue(Hooks::AUTOMATION_STEP, [
        [
          'automation_run_id' => $automationRunId,
          'step_id' => $nextStep ? $nextStep->getId() : null,
        ],
      ]);

      $this->automationRunStorage->updateNextStep($automationRunId, $nextStep ? $nextStep->getId() : null);
    }
  }
}
