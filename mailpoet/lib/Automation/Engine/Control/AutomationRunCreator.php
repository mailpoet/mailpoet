<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\WordPress;
use RuntimeException;
use Throwable;

class AutomationRunCreator {
  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var SubjectLoader */
  private $subjectLoader;

  /** @var SubjectTransformerHandler */
  private $subjectTransformerHandler;

  /** @var FilterHandler */
  private $filterHandler;

  /** @var StepScheduler */
  private $stepScheduler;

  /** @var StepRunLoggerFactory */
  private $stepRunLoggerFactory;

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    AutomationRunStorage $automationRunStorage,
    SubjectLoader $subjectLoader,
    SubjectTransformerHandler $subjectTransformerHandler,
    FilterHandler $filterHandler,
    StepScheduler $stepScheduler,
    StepRunLoggerFactory $stepRunLoggerFactory,
    WordPress $wordPress
  ) {
    $this->automationRunStorage = $automationRunStorage;
    $this->subjectLoader = $subjectLoader;
    $this->subjectTransformerHandler = $subjectTransformerHandler;
    $this->filterHandler = $filterHandler;
    $this->stepScheduler = $stepScheduler;
    $this->stepRunLoggerFactory = $stepRunLoggerFactory;
    $this->wordPress = $wordPress;
  }

  /**
   * @param Subject[] $subjects
   * @param array<string, scalar|array<array-key, scalar>> $triggerLogData
   */
  public function createForAutomation(
    Automation $automation,
    Trigger $trigger,
    array $subjects,
    array $triggerLogData = []
  ): AutomationRunCreationResult {
    $subjects = $this->subjectTransformerHandler->getAllSubjects($subjects);
    $subjectEntries = $this->subjectLoader->getSubjectsEntries($subjects);

    $step = $automation->getTrigger($trigger->getKey());
    if (!$step) {
      throw Exceptions::automationTriggerNotFound($automation->getId(), $trigger->getKey());
    }

    $automationRun = new AutomationRun($automation->getId(), $automation->getVersionId(), $trigger->getKey(), $subjects);
    $stepRunArgs = new StepRunArgs($automation, $automationRun, $step, $subjectEntries, 1);

    $match = false;
    try {
      $match = $this->filterHandler->matchesFilters($stepRunArgs);
    } catch (Exceptions\Exception $e) {
      // Failed trigger filter evaluation does not match.
    }
    if (!$match) {
      return AutomationRunCreationResult::skipped(AutomationRunCreationResult::STATUS_TRIGGER_FILTER_MISMATCH);
    }

    $triggerMatches = $trigger->isTriggeredBy($stepRunArgs);
    $createAutomationRun = $this->wordPress->applyFilters(
      Hooks::AUTOMATION_RUN_CREATE,
      $triggerMatches,
      $stepRunArgs
    );
    if (!$createAutomationRun) {
      return AutomationRunCreationResult::skipped(
        $triggerMatches
          ? AutomationRunCreationResult::STATUS_RUN_CREATE_HOOK_REJECTED
          : AutomationRunCreationResult::STATUS_TRIGGER_FILTER_MISMATCH
      );
    }

    $automationRunId = $this->automationRunStorage->createAutomationRun($automationRun);
    $automationRun->setId($automationRunId);

    $logger = $this->stepRunLoggerFactory->createLogger($automationRunId, $step->getId(), AutomationRunLog::TYPE_TRIGGER, 1);
    $logger->logStepData($step);
    if ($triggerLogData) {
      $logger->saveLogData($triggerLogData);
    }

    try {
      $schedulingResult = $this->stepScheduler->scheduleNextStepWithResult($stepRunArgs);
    } catch (Throwable $error) {
      $this->automationRunStorage->updateStatus($automationRunId, AutomationRun::STATUS_FAILED);
      $logger->logFailure($error);
      return AutomationRunCreationResult::skipped(AutomationRunCreationResult::STATUS_STEP_SCHEDULING_FAILED);
    }

    if ($schedulingResult->isEnqueueFailed()) {
      $this->automationRunStorage->updateStatus($automationRunId, AutomationRun::STATUS_FAILED);
      $logger->logFailure(new RuntimeException(__('Could not schedule the next automation step.', 'mailpoet')));
      return AutomationRunCreationResult::schedulingFailed($automationRunId, $schedulingResult);
    }

    $logger->logSuccess();
    return AutomationRunCreationResult::created($automationRunId, $schedulingResult);
  }
}
