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

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var Functions  */
  private $wp;

  /** @var SubjectLoader */
  private $subjectLoader;

  /** @var SubjectTransformerHandler */
  private $subjectTransformerHandler;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    ActionScheduler $actionScheduler,
    AutomationStorage $automationStorage,
    AutomationRunStorage $automationRunStorage,
    Functions $wp,
    SubjectLoader $subjectLoader,
    SubjectTransformerHandler $subjectTransformerHandler,
    WordPress $wordPress
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->automationStorage = $automationStorage;
    $this->automationRunStorage = $automationRunStorage;
    $this->wp = $wp;
    $this->subjectLoader = $subjectLoader;
    $this->subjectTransformerHandler = $subjectTransformerHandler;
    $this->wordPress = $wordPress;
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

    // expand all subject transformations and load subject entries
    $subjects = $this->subjectTransformerHandler->getAllSubjects($subjects);
    $subjectEntries = $this->subjectLoader->getSubjectsEntries($subjects);

    foreach ($automations as $automation) {
      $step = $automation->getTrigger($trigger->getKey());
      if (!$step) {
        throw Exceptions::automationTriggerNotFound($automation->getId(), $trigger->getKey());
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
