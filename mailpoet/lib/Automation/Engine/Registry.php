<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Workflows\ActionInterface;
use MailPoet\Automation\Engine\Workflows\Trigger;

class Registry {
  /** @var array<string, Trigger> */
  private $triggers = [];

  /** @var array<string, ActionInterface> */
  private $actions = [];

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

  public function addAction(ActionInterface $action): void {
    $key = $action->getKey();
    if (isset($this->actions[$key])) {
      throw new \Exception(); // TODO
    }
    $this->actions[$key] = $action;
  }

  public function getAction(string $key): ?ActionInterface {
    return $this->actions[$key] ?? null;
  }

  /** @return array<string, ActionInterface> */
  public function getActions(): array {
    return $this->actions;
  }
}
