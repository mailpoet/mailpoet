<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Hooks;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class CreateAutomationRunHook {
  private AutomationRunStorage $automationRunStorage;
  private WPFunctions $wp;

  public function __construct(
    AutomationRunStorage $automationRunStorage,
    WPFunctions $wp
  ) {
    $this->automationRunStorage = $automationRunStorage;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::AUTOMATION_RUN_CREATE, [$this, 'createAutomationRun'], 5, 2);
  }

  public function createAutomationRun(bool $result, StepRunArgs $args): bool {
    if (!$result) {
      return $result;
    }

    $automation = $args->getAutomation();
    $runOnlyOnce = $automation->getMeta('mailpoet:run-once-per-subscriber');
    if (!$runOnlyOnce) {
      return true;
    }

    $subscriberSubject = array_values($args->getAutomationRun()->getSubjects(SubscriberSubject::KEY))[0] ?? null;
    if (!$subscriberSubject) {
      return true;
    }

    // Use locking mechanism to minimize the risk of race conditions.
    // WP transients don't provide atomic operations, so we can't guarantee
    // race-condition safety with a 100% certainty, but we can significantly
    // minimize the risk by generating and re-checking a unique lock value.
    $key = sprintf('mailpoet:run-once-per-subscriber:[%s][%s]', $automation->getId(), $subscriberSubject->getHash());

    // 1. If lock already exists, do not create automation run.
    $value = $this->wp->getTransient($key);
    if ($value) {
      return false;
    }

    // 2. If lock does not exist, create it with a unique value.
    $value = Security::generateRandomString(16);
    $this->wp->setTransient($key, $value, MINUTE_IN_SECONDS);

    // 3. If no automation run exist, ensure that the lock wasn't updated by another process.
    $count = $this->automationRunStorage->getCountByAutomationAndSubject($automation, $subscriberSubject);
    return $count === 0 && $this->wp->getTransient($key) === $value;
  }
}
