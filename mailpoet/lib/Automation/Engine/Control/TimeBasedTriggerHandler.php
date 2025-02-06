<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Integration\TimeBasedTrigger;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;

class TimeBasedTriggerHandler {


  public const KICK_OFF_HOOK = 'mailpoet_automation_time_based_run';

  public const SINGLE_AUTOMATION_HOOK = 'mailpoet_automation_time_based_trigger';

  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Registry */
  private $registry;

  /** @var WordPress */
  private $wp;

  public function __construct(
    ActionScheduler $actionScheduler,
    AutomationStorage $automationStorage,
    Registry $registry,
    WordPress $wp
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->automationStorage = $automationStorage;
    $this->registry = $registry;
    $this->wp = $wp;

  }

  public function initialize(): void {
    $this->wp->addAction(self::KICK_OFF_HOOK, [$this, 'run']);
    $this->wp->addAction(self::SINGLE_AUTOMATION_HOOK, [$this, 'runSingleAutomation']);
    if ($this->actionScheduler->hasScheduledAction(self::KICK_OFF_HOOK)) {
      return;
    }

    $this->actionScheduler->scheduleRecurring(
      time(),
      DAY_IN_SECONDS,
      self::KICK_OFF_HOOK
    );
  }

  public function run(): void {

    $triggers = array_filter(
      $this->registry->getTriggers(),
      function (Trigger $trigger): bool {
        return $trigger instanceof TimeBasedTrigger;
      }
    );

    $activeTimeBasedAutomations = array_filter(
      array_map(
        function (TimeBasedTrigger $trigger) {
          return [
            'automations' => $this->automationStorage->getActiveAutomationsByTrigger($trigger),
            'trigger' => $trigger,
          ];
        },
        $triggers
      ),
      function ($automations) {
        return !empty($automations['automations']);
      }
    );

    foreach ($activeTimeBasedAutomations as $automations) {
      foreach ($automations['automations'] as $automation) {
        $this->actionScheduler->schedule(
          time() - 1,
          self::SINGLE_AUTOMATION_HOOK,
          [
            'automation_id' => $automation->getId(),
            'offset' => 0,
            'trigger' => $automations['trigger']->getKey(),
          ]
        );
      }
    }
  }

  /** @param mixed $args */
  public function runSingleAutomation($args): void {
    if (!is_array($args)) {
      return;
    }
    if (!isset($args['automation_id'])) {
      return;
    }

    $automationId = (int)$args['automation_id'];
    $triggerKey = isset($args['trigger']) ? $args['trigger'] : null;
    $offset = isset($args['offset']) ? (int)$args['offset'] : 0;

    $automation = $this->automationStorage->getAutomation($automationId);
    $trigger = $this->registry->getTrigger($triggerKey);

    if (!$automation || $automation->getStatus() !== Automation::STATUS_ACTIVE || !$trigger instanceof TimeBasedTrigger) {
      return;
    }
    $triggerData = $automation->getTrigger($triggerKey);
    if (!$triggerData) {
      return;
    }

    $items = $trigger->findItemsToTrigger($automation, $triggerData, $offset);
    if ($items < $trigger->getLimit()) {
      return;
    }
    $this->actionScheduler->schedule(
      time() - 1,
      self::SINGLE_AUTOMATION_HOOK,
      [
        'automation_id' => $automationId,
        'offset' => $offset + $trigger->getLimit(),
        'trigger' => $triggerKey,
      ]
    );
  }
}
