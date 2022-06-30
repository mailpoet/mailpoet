<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Engine\Workflows\Trigger;

class Registry {
  /** @var array<string, Subject> */
  private $subjects = [];

  /** @var array<string, Trigger> */
  private $triggers = [];

  /** @var array<string, Action> */
  private $actions = [];

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

  public function addTrigger(Trigger $trigger): void {
    $key = $trigger->getKey();
    if (isset($this->triggers[$key])) {
      throw new \Exception(); // TODO
    }
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
    if (isset($this->actions[$key])) {
      throw new \Exception(); // TODO
    }
    $this->actions[$key] = $action;
  }

  public function getAction(string $key): ?Action {
    return $this->actions[$key] ?? null;
  }

  /** @return array<string, Action> */
  public function getActions(): array {
    return $this->actions;
  }
}
