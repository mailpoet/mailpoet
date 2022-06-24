<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\SubjectFactory;
use MailPoet\Automation\Engine\Workflows\Trigger;

class Registry {
  /** @var array<string, Trigger> */
  private $triggers = [];

  /** @var array<string, Action> */
  private $actions = [];

  /** @var array<SubjectFactory> */
  private $subjectFactories = [];

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

  public function addSubjectFactory(SubjectFactory $factory): void {
      $this->subjectFactories[] = $factory;
  }

  /** @return SubjectFactory[] */
  public function getSubjectFactories(): array {
    return $this->subjectFactories;
  }
}
