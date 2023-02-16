<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Hooks;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\WP\Functions;

class CreateAutomationRunHook {


  /** @var Functions */
  private $wp;

  private $automationRunStorage;

  public function __construct(
    Functions $wp,
    AutomationRunStorage $automationRunStorage
  ) {
    $this->wp = $wp;
    $this->automationRunStorage = $automationRunStorage;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::AUTOMATION_RUN_CREATE, [$this, 'createAutomationRun'], 5, 2);
  }

  public function createAutomationRun(bool $result, StepRunArgs $args): bool {
    if (!$result) {
      return $result;
    }

    $automation = $args->getAutomation();
    $runOnlyOnce = $automation->getMeta('run_automation_once');
    if (!$runOnlyOnce) {
      return true;
    }

    $subscriberSubject = $args->getAutomationRun()->getSubjects(SubscriberSubject::KEY);
    if (!$subscriberSubject) {
      return true;
    }

    return $this->automationRunStorage->countRunsForAutomationAndSubject($automation, current($subscriberSubject)) === 0;
  }
}
