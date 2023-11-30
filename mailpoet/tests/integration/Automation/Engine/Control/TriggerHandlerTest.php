<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use MailPoet\Automation\Engine\Control\TriggerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class TriggerHandlerTest extends \MailPoetTest {


  /** @var TriggerHandler */
  private $testee;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SegmentEntity[] */
  private $segments;

  public function _before() {
    $this->testee = $this->diContainer->get(TriggerHandler::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);

    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->segments = [
      'segment_1' => $this->segmentRepository->createOrUpdate('Segment 1'),
      'segment_2' => $this->segmentRepository->createOrUpdate('Segment 2'),
    ];

  }

  /**
   * This test creates two automations with the same trigger, but different arguments.
   * We check whether the correct automation creates a run.
   */
  public function testItCreatesRunForTheCorrectAutomationWhenTwoAutomationsHaveTheSameTrigger() {

    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $automation1 = $this->tester->createAutomation(
      'automation-1',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        $trigger->getKey(),
        [
          'segment_ids' => [$this->segments['segment_1']->getId()],
        ],
        []
      )
    );
    $automation2 = $this->tester->createAutomation(
      'automation-2',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        $trigger->getKey(),
        [
          'segment_ids' => [$this->segments['segment_2']->getId()],
        ],
        []
      )
    );
    $this->assertInstanceOf(Automation::class, $automation1);
    $this->assertInstanceOf(Automation::class, $automation2);

    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation1));
    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation2));

    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_1']->getId()]);
    $this->testee->processTrigger($trigger, [$segmentSubject]);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation1));
    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation2));

    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_2']->getId()]);
    $this->testee->processTrigger($trigger, [$segmentSubject]);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation1));
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation2));
  }

  /**
   * This test ensures the trigger handler can create runs for several automations at once.
   */
  public function testItCreatesTwoRunsWhenTwoAutomationsWithSameTriggerAreTriggered() {

    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $automation1 = $this->tester->createAutomation(
      'automation-1',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        $trigger->getKey(),
        [
          'segment_ids' => [$this->segments['segment_1']->getId()],
        ],
        []
      )
    );
    $automation2 = $this->tester->createAutomation(
      'automation-2',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        $trigger->getKey(),
        [
          'segment_ids' => [],
        ],
        []
      )
    );
    $this->assertInstanceOf(Automation::class, $automation1);
    $this->assertInstanceOf(Automation::class, $automation2);

    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation1));
    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation2));

    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_1']->getId()]);
    $this->testee->processTrigger($trigger, [$segmentSubject]);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation1));
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation2));
  }

  /**
   * This test ensures that runs are not created if the triggers do not match.
   */
  public function testItCreatesNoRunsForAutomationsWithADifferentTrigger() {

    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $anotherTrigger = $this->diContainer->get(UserRegistrationTrigger::class);
    $automation1 = $this->tester->createAutomation(
      'automation-1',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        $anotherTrigger->getKey(),
        [],
        []
      )
    );
    $this->assertInstanceOf(Automation::class, $automation1);

    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation1));

    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_1']->getId()]);
    $this->testee->processTrigger($trigger, [$segmentSubject]);
    $this->assertEmpty($this->automationRunStorage->getAutomationRunsForAutomation($automation1));
  }

  public function testItAppliesFilters(): void {
    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $subscriber = (new Subscriber())->create();
    $subscriberSubject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]);
    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_1']->getId()]);

    // dynamic segment without any filters (matches all subscribers)
    $segment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    // automation that doesn't match segments filter
    $unknownId = $segment->getId() + 1;
    $filter = new Filter('f1', 'enum_array', 'mailpoet:subscriber:segments', 'matches-any-of', ['value' => [$unknownId]]);
    $filters = new Filters('and', [new FilterGroup('g1', 'and', [$filter])]);
    $automation = $this->tester->createAutomation(
      'Will not run',
      new Step('trigger', Step::TYPE_TRIGGER, $trigger->getKey(), [], [], $filters)
    );
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $this->testee->processTrigger($trigger, [$segmentSubject, $subscriberSubject]);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    // matches segments filter
    $filter = new Filter('f1', 'enum_array', 'mailpoet:subscriber:segments', 'matches-any-of', ['value' => [$segment->getId()]]);
    $filters = new Filters('and', [new FilterGroup('g1', 'and', [$filter])]);
    $automation = $this->tester->createAutomation(
      'Will run',
      new Step('trigger', Step::TYPE_TRIGGER, $trigger->getKey(), [], [], $filters)
    );
    $this->assertInstanceOf(Automation::class, $automation);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $this->testee->processTrigger($trigger, [$segmentSubject, $subscriberSubject]);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItLogs(): void {
    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $automation1 = $this->tester->createAutomation('Test 1', new Step('trigger-1', Step::TYPE_TRIGGER, $trigger->getKey(), [], []));
    $this->assertInstanceOf(Automation::class, $automation1);
    $automation2 = $this->tester->createAutomation('Test 2', new Step('trigger-2', Step::TYPE_TRIGGER, $trigger->getKey(), [], []));
    $this->assertInstanceOf(Automation::class, $automation2);

    $segmentSubject = new Subject(SegmentSubject::KEY, ['segment_id' => $this->segments['segment_1']->getId()]);
    $this->testee->processTrigger($trigger, [$segmentSubject]);

    $runs1 = $this->automationRunStorage->getAutomationRunsForAutomation($automation1);
    $this->assertCount(1, $runs1);

    $logs1 = $this->automationRunLogStorage->getLogsForAutomationRun($runs1[0]->getId());
    $this->assertCount(1, $logs1);
    $this->assertSame($runs1[0]->getId(), $logs1[0]->getAutomationRunId());
    $this->assertSame('trigger-1', $logs1[0]->getStepId());
    $this->assertSame($trigger->getKey(), $logs1[0]->getStepKey());
    $this->assertSame('complete', $logs1[0]->getStatus());
    $this->assertSame(1, $logs1[0]->getRunNumber());

    $runs2 = $this->automationRunStorage->getAutomationRunsForAutomation($automation2);
    $this->assertCount(1, $runs2);

    $logs2 = $this->automationRunLogStorage->getLogsForAutomationRun($runs2[0]->getId());
    $this->assertCount(1, $logs2);
    $this->assertSame($runs2[0]->getId(), $logs2[0]->getAutomationRunId());
    $this->assertSame('trigger-2', $logs2[0]->getStepId());
    $this->assertSame($trigger->getKey(), $logs2[0]->getStepKey());
    $this->assertSame('complete', $logs2[0]->getStatus());
    $this->assertSame(1, $logs2[0]->getRunNumber());
  }
}
