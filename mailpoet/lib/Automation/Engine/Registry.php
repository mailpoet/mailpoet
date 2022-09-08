<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Engine\Workflows\Trigger;

class Registry {
  /** @var array<string, Step> */
  private $steps = [];

  /** @var array<string, Subject> */
  private $subjects = [];

  /** @var array<string, Trigger> */
  private $triggers = [];

  /** @var array<string, Action> */
  private $actions = [];

  /** @var WordPress */
  private $wordPress;

  public function __construct(
    RootStep $rootStep,
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
    $this->steps[$rootStep->getKey()] = $rootStep;
  }

  public function addSubject(Subject $subject): void {
    $key = $subject->getKey();
    if (isset($this->subjects[$key])) {
      throw new \Exception(); // TODO
    }
    $this->subjects[$key] = $subject;
  }

  public function getSubject(string $key): ?Subject {
    return $this->subjects[$key] ?? null;
  }

  /** @return array<string, Subject> */
  public function getSubjects(): array {
    return $this->subjects;
  }

  public function addStep(Step $step): void {
    if ($step instanceof Trigger) {
      $this->addTrigger($step);
    } elseif ($step instanceof Action) {
      $this->addAction($step);
    }

    // TODO: allow adding any other step implementations?
  }

  public function getStep(string $key): ?Step {
    return $this->steps[$key] ?? null;
  }

  /** @return array<string, Step> */
  public function getSteps(): array {
    return $this->steps;
  }

  public function addTrigger(Trigger $trigger): void {
    $key = $trigger->getKey();
    if (isset($this->steps[$key]) || isset($this->triggers[$key])) {
      throw new \Exception(); // TODO
    }
    $this->steps[$key] = $trigger;
    $this->triggers[$key] = $trigger;
  }

  public function getTrigger(string $key): ?Trigger {
    return $this->triggers[$key] ?? null;
  }

  /** @return array<string, Trigger> */
  public function getTriggers(): array {
    return $this->triggers;
  }

  public function addAction(Action $action): void {
    $key = $action->getKey();
    if (isset($this->steps[$key]) || isset($this->actions[$key])) {
      throw new \Exception(); // TODO
    }
    $this->steps[$key] = $action;
    $this->actions[$key] = $action;
  }

  public function getAction(string $key): ?Action {
    return $this->actions[$key] ?? null;
  }

  /** @return array<string, Action> */
  public function getActions(): array {
    return $this->actions;
  }

  public function onBeforeWorkflowSave(callable $callback, int $priority = 10): void {
    $this->wordPress->addAction(Hooks::WORKFLOW_BEFORE_SAVE, $callback, $priority);
  }

  public function onBeforeWorkflowStepSave(callable $callback, string $key = null, int $priority = 10): void {
    $keyPart = $key ? "/key=$key" : '';
    $this->wordPress->addAction(Hooks::WORKFLOW_STEP_BEFORE_SAVE . $keyPart, $callback, $priority);
  }
}
