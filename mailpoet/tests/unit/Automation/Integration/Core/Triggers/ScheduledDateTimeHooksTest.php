<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\Core\Triggers\ScheduledDateTimeHooks;
use MailPoet\Automation\Integrations\Core\Triggers\ScheduledDateTimeTrigger;
use MailPoetUnitTest;

class ScheduledDateTimeHooksTest extends MailPoetUnitTest {
  /** @var ScheduledDateTimeHooks */
  private $hooks;

  /** @var WordPress&\PHPUnit\Framework\MockObject\MockObject */
  private $wp;

  /** @var ActionScheduler&\PHPUnit\Framework\MockObject\MockObject */
  private $actionScheduler;

  /** @var AutomationStorage&\PHPUnit\Framework\MockObject\MockObject */
  private $automationStorage;

  public function _before() {
    $this->wp = $this->createMock(WordPress::class);
    $this->actionScheduler = $this->createMock(ActionScheduler::class);
    $this->automationStorage = $this->createMock(AutomationStorage::class);

    $this->hooks = new ScheduledDateTimeHooks(
      $this->wp,
      $this->actionScheduler,
      $this->automationStorage
    );
  }

  public function testInitRegistersBeforeSaveHook(): void {
    $this->wp->expects($this->once())
      ->method('addAction')
      ->with(Hooks::AUTOMATION_BEFORE_SAVE, [$this->hooks, 'handleBeforeSave']);

    $this->hooks->init();
  }

  public function testSchedulesJobOnActivation(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour');
    $trigger = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate->format(DateTimeImmutable::W3C),
      'segment_ids' => [1],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getId')->willReturn(5);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($trigger);

    // Previous automation was draft
    $previousAutomation = $this->createMock(Automation::class);
    $previousAutomation->method('getStatus')->willReturn(Automation::STATUS_DRAFT);
    $this->automationStorage->method('getAutomation')->with(5)->willReturn($previousAutomation);

    $this->actionScheduler->expects($this->once())
      ->method('schedule')
      ->with(
        $futureDate->getTimestamp(),
        ScheduledDateTimeTrigger::SCHEDULED_HOOK,
        [5, 0]
      );

    $this->hooks->handleBeforeSave($automation);
  }

  public function testCancelsAllJobsOnDeactivation(): void {
    $trigger = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => '2030-01-01T00:00:00+00:00',
      'segment_ids' => [1],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getId')->willReturn(5);
    $automation->method('getStatus')->willReturn(Automation::STATUS_DRAFT);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($trigger);

    // Previous automation was active
    $previousAutomation = $this->createMock(Automation::class);
    $previousAutomation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $this->automationStorage->method('getAutomation')->with(5)->willReturn($previousAutomation);

    // Two pending actions: initial (offset 0) and in-flight batch (cursor 500)
    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- matching ActionScheduler_Action API
    $action1 = new class {
      /** @return int[] */
      public function get_args(): array {
        return [5, 0];
      }
    };
    $action2 = new class {
      /** @return int[] */
      public function get_args(): array {
        return [5, 500];
      }
    };
    // Also an action for a different automation — should NOT be cancelled
    $action3 = new class {
      /** @return int[] */
      public function get_args(): array {
        return [99, 0];
      }
    };
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    $this->actionScheduler->method('getScheduledActions')->willReturn([
      1 => $action1,
      2 => $action2,
      3 => $action3,
    ]);

    $this->actionScheduler->expects($this->exactly(2))
      ->method('unscheduleAction')
      ->withConsecutive(
        [ScheduledDateTimeTrigger::SCHEDULED_HOOK, [5, 0]],
        [ScheduledDateTimeTrigger::SCHEDULED_HOOK, [5, 500]]
      );

    $this->hooks->handleBeforeSave($automation);
  }

  public function testDoesNothingWhenStatusUnchanged(): void {
    $trigger = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => '2030-01-01T00:00:00+00:00',
      'segment_ids' => [1],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getId')->willReturn(5);
    $automation->method('getStatus')->willReturn(Automation::STATUS_DRAFT);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($trigger);

    // Previous was also draft
    $previousAutomation = $this->createMock(Automation::class);
    $previousAutomation->method('getStatus')->willReturn(Automation::STATUS_DRAFT);
    $this->automationStorage->method('getAutomation')->with(5)->willReturn($previousAutomation);

    $this->actionScheduler->expects($this->never())->method('schedule');
    $this->actionScheduler->expects($this->never())->method('unscheduleAction');

    $this->hooks->handleBeforeSave($automation);
  }

  public function testDoesNothingForAutomationsWithoutScheduledTrigger(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn(null);

    $this->actionScheduler->expects($this->never())->method('schedule');
    $this->actionScheduler->expects($this->never())->method('unscheduleAction');

    $this->hooks->handleBeforeSave($automation);
  }

  public function testSchedulesJobForNewAutomationWithNoPreviousVersion(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour');
    $trigger = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate->format(DateTimeImmutable::W3C),
      'segment_ids' => [1],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getId')->willReturn(99);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($trigger);

    // No previous version in storage
    $this->automationStorage->method('getAutomation')->with(99)->willReturn(null);

    $this->actionScheduler->expects($this->once())
      ->method('schedule')
      ->with(
        $futureDate->getTimestamp(),
        ScheduledDateTimeTrigger::SCHEDULED_HOOK,
        [99, 0]
      );

    $this->hooks->handleBeforeSave($automation);
  }
}
