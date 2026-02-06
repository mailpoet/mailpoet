<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\Core\Triggers\ScheduledDateTimeTrigger;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoetUnitTest;

class ScheduledDateTimeTriggerTest extends MailPoetUnitTest {
  /** @var ScheduledDateTimeTrigger */
  private $trigger;

  /** @var WordPress&\PHPUnit\Framework\MockObject\MockObject */
  private $wp;

  /** @var ActionScheduler&\PHPUnit\Framework\MockObject\MockObject */
  private $actionScheduler;

  /** @var AutomationStorage&\PHPUnit\Framework\MockObject\MockObject */
  private $automationStorage;

  /** @var SegmentSubscribersRepository&\PHPUnit\Framework\MockObject\MockObject */
  private $segmentSubscribersRepository;

  public function _before() {
    $this->wp = $this->createMock(WordPress::class);
    $this->actionScheduler = $this->createMock(ActionScheduler::class);
    $this->automationStorage = $this->createMock(AutomationStorage::class);
    $this->segmentSubscribersRepository = $this->createMock(SegmentSubscribersRepository::class);

    $this->trigger = new ScheduledDateTimeTrigger(
      $this->wp,
      $this->actionScheduler,
      $this->automationStorage,
      $this->segmentSubscribersRepository
    );
  }

  public function testGetKey(): void {
    $this->assertSame('core:scheduled-date-time', $this->trigger->getKey());
  }

  public function testGetName(): void {
    $this->assertSame('Scheduled date/time', $this->trigger->getName());
  }

  public function testGetSubjectKeys(): void {
    $this->assertSame(
      [SubscriberSubject::KEY, SegmentSubject::KEY],
      $this->trigger->getSubjectKeys()
    );
  }

  public function testGetArgsSchema(): void {
    $schema = $this->trigger->getArgsSchema()->toArray();
    $this->assertSame('object', $schema['type']);
    $this->assertArrayHasKey('scheduled_at', $schema['properties']);
    $this->assertArrayHasKey('segment_ids', $schema['properties']);
    $this->assertSame('string', $schema['properties']['scheduled_at']['type']);
    $this->assertSame('date-time', $schema['properties']['scheduled_at']['format']);
    $this->assertSame('array', $schema['properties']['segment_ids']['type']);
  }

  public function testValidateRejectsEmptyScheduledAt(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('needsFullValidation')->willReturn(true);

    $step = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [], []);
    $args = new StepValidationArgs($automation, $step, []);

    $this->expectException(ValidationException::class);
    $this->trigger->validate($args);
  }

  public function testValidateRejectsPastDate(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('needsFullValidation')->willReturn(true);

    $pastDate = (new DateTimeImmutable())->modify('-1 hour')->format(DateTimeImmutable::W3C);
    $step = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, ['scheduled_at' => $pastDate, 'segment_ids' => [1]], []);
    $args = new StepValidationArgs($automation, $step, []);

    $this->expectException(ValidationException::class);
    $this->trigger->validate($args);
  }

  public function testValidateAcceptsFutureDate(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('needsFullValidation')->willReturn(true);

    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $step = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, ['scheduled_at' => $futureDate, 'segment_ids' => [1]], []);
    $args = new StepValidationArgs($automation, $step, []);

    $this->trigger->validate($args);
    // No exception means validation passed
    $this->assertTrue(true);
  }

  public function testValidateSkipsForDraftAutomations(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('needsFullValidation')->willReturn(false);

    $pastDate = (new DateTimeImmutable())->modify('-1 hour')->format(DateTimeImmutable::W3C);
    $step = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, ['scheduled_at' => $pastDate, 'segment_ids' => [1]], []);
    $args = new StepValidationArgs($automation, $step, []);

    $this->trigger->validate($args);
    // No exception for drafts even with past date
    $this->assertTrue(true);
  }

  public function testIsTriggeredByReturnsFalseWithoutCurrentAutomation(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('getId')->willReturn(1);

    $args = new StepRunArgs(
      $automation,
      $this->createMock(AutomationRun::class),
      new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [], []),
      [],
      1
    );

    $this->assertFalse($this->trigger->isTriggeredBy($args));
  }

  public function testHandleAbortIfAutomationInactive(): void {
    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_DRAFT);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);

    $this->wp->expects($this->never())->method('doAction');

    $this->trigger->handle(1, 0);
  }

  public function testHandleAbortIfAutomationNotFound(): void {
    $this->automationStorage->method('getAutomation')->with(999)->willReturn(null);

    $this->wp->expects($this->never())->method('doAction');

    $this->trigger->handle(999, 0);
  }

  public function testHandleFiresTriggerForEachSubscriber(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->with([10], 0, 100)
      ->willReturn([101, 102, 103]);

    $this->wp->expects($this->exactly(3))->method('doAction');

    $this->trigger->handle(1, 0);
  }

  public function testHandleSchedulesNextBatchWhenFullBatchReturned(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);

    // Return exactly BATCH_SIZE subscribers — indicates more may exist
    $subscriberIds = range(1, 100);
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->with([10], 0, 100)
      ->willReturn($subscriberIds);

    $this->wp->expects($this->exactly(100))->method('doAction');
    // Cursor is the last subscriber ID in the batch
    $this->actionScheduler->expects($this->once())
      ->method('enqueue')
      ->with(ScheduledDateTimeTrigger::SCHEDULED_HOOK, [1, 100]);

    $this->trigger->handle(1, 0);
  }

  public function testHandleDoesNotScheduleNextBatchIfPartialBatch(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);
    // Fewer than BATCH_SIZE — no more subscribers
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->with([10], 0, 100)
      ->willReturn([101, 102]);

    $this->actionScheduler->expects($this->never())->method('enqueue');

    $this->trigger->handle(1, 0);
  }

  public function testHandleCleansUpCurrentAutomationIdAfterSuccess(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->willReturn([101]);

    $this->trigger->handle(1, 0);

    // After handle() completes, isTriggeredBy should return false
    $runArgs = new StepRunArgs(
      $automation,
      $this->createMock(AutomationRun::class),
      $triggerStep,
      [],
      1
    );
    $this->assertFalse($this->trigger->isTriggeredBy($runArgs));
  }

  public function testHandleCleansUpCurrentAutomationIdOnException(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->willThrowException(new \RuntimeException('Segment query failed'));

    try {
      $this->trigger->handle(1, 0);
    } catch (\RuntimeException $e) {
      // Expected
    }

    // currentAutomationId must be cleaned up even after exception
    $runArgs = new StepRunArgs(
      $automation,
      $this->createMock(AutomationRun::class),
      $triggerStep,
      [],
      1
    );
    $this->assertFalse($this->trigger->isTriggeredBy($runArgs));
  }

  public function testHandleSecondBatchUsesCursorCorrectly(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);

    // Second batch: cursor is 500 (last processed ID from first batch)
    $this->segmentSubscribersRepository->expects($this->once())
      ->method('getSubscriberIdsInSegments')
      ->with([10], 500, 100)
      ->willReturn([501, 502, 503]);

    $this->wp->expects($this->exactly(3))->method('doAction');
    $this->actionScheduler->expects($this->never())->method('enqueue');

    $this->trigger->handle(1, 500);
  }

  public function testHandleWithZeroSubscribers(): void {
    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $triggerStep = new Step('t1', Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
      'scheduled_at' => $futureDate,
      'segment_ids' => [10],
    ], []);

    $automation = $this->createMock(Automation::class);
    $automation->method('getStatus')->willReturn(Automation::STATUS_ACTIVE);
    $automation->method('getId')->willReturn(1);
    $automation->method('getTrigger')->with(ScheduledDateTimeTrigger::KEY)->willReturn($triggerStep);

    $this->automationStorage->method('getAutomation')->with(1)->willReturn($automation);
    $this->segmentSubscribersRepository->method('getSubscriberIdsInSegments')
      ->willReturn([]);

    $this->wp->expects($this->never())->method('doAction');
    $this->actionScheduler->expects($this->never())->method('enqueue');

    $this->trigger->handle(1, 0);
  }
}
