<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use MailPoet\Automation\Engine\Control\TriggerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class TriggerHandlerTest extends \MailPoetTest {


  /** @var TriggerHandler */
  private $testee;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SegmentEntity[] */
  private $segments;

  public function _before() {
    $this->testee = $this->diContainer->get(TriggerHandler::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);

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

  public function _after() {
    parent::_after();
    $this->automationRunStorage->truncate();
    $this->automationStorage->truncate();
    $this->segmentRepository->truncate();
  }
}
