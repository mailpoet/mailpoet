<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Triggers\ScheduledDateTimeTrigger;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class ScheduledDateTimeTriggerTest extends \MailPoetTest {
  /** @var ScheduledDateTimeTrigger */
  private $trigger;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function _before() {
    $this->trigger = $this->diContainer->get(ScheduledDateTimeTrigger::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
  }

  public function testHandleCreatesRunsForSubscribersInSegment(): void {
    $segment = (new SegmentFactory())->withName('Test Segment')->create();
    $segmentId = (int)$segment->getId();

    (new SubscriberFactory())
      ->withEmail('sub1@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    (new SubscriberFactory())
      ->withEmail('sub2@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $automation = $this->createAutomationWithTrigger($futureDate, [$segmentId]);

    $this->trigger->registerHooks();
    $this->trigger->handle($automation->getId(), 0);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(2, $runs);
  }

  public function testHandleDoesNotCreateRunsForInactiveAutomation(): void {
    $segment = (new SegmentFactory())->withName('Test Segment')->create();
    $segmentId = (int)$segment->getId();

    (new SubscriberFactory())
      ->withEmail('sub@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $automation = $this->createAutomationWithTrigger($futureDate, [$segmentId], Automation::STATUS_DRAFT);

    $this->trigger->registerHooks();
    $this->trigger->handle($automation->getId(), 0);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(0, $runs);
  }

  public function testHandleDeduplicatesSubscribersAcrossSegments(): void {
    $segment1 = (new SegmentFactory())->withName('Segment A')->create();
    $segment2 = (new SegmentFactory())->withName('Segment B')->create();
    $segmentId1 = (int)$segment1->getId();
    $segmentId2 = (int)$segment2->getId();

    // Subscriber in both segments — should only get one run
    (new SubscriberFactory())
      ->withEmail('shared@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment1, $segment2])
      ->create();

    // Subscriber only in segment 2
    (new SubscriberFactory())
      ->withEmail('only-seg2@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment2])
      ->create();

    $futureDate = (new DateTimeImmutable())->modify('+1 hour')->format(DateTimeImmutable::W3C);
    $automation = $this->createAutomationWithTrigger($futureDate, [$segmentId1, $segmentId2]);

    $this->trigger->registerHooks();
    $this->trigger->handle($automation->getId(), 0);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    // 2 unique subscribers, not 3 (shared subscriber counted once)
    $this->assertCount(2, $runs);
  }

  /**
   * @param int[] $segmentIds
   */
  private function createAutomationWithTrigger(
    string $scheduledAt,
    array $segmentIds,
    string $status = Automation::STATUS_ACTIVE
  ): Automation {
    $triggerId = 'trigger-' . uniqid();
    $actionId = 'action-' . uniqid();

    return (new AutomationFactory())
      ->withSteps([
        new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep($triggerId)]),
        new Step($triggerId, Step::TYPE_TRIGGER, ScheduledDateTimeTrigger::KEY, [
          'scheduled_at' => $scheduledAt,
          'segment_ids' => $segmentIds,
        ], [new NextStep($actionId)]),
        new Step($actionId, Step::TYPE_ACTION, 'core:delay', [
          'delay_type' => 'MINUTES',
          'delay' => 1,
        ], []),
      ])
      ->withStatus($status)
      ->create();
  }
}
