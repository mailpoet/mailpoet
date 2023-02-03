<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;

class SomeoneSubscribesTriggerTest extends \MailPoetTest {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentEntity[] */
  private $segments;

  public function _before() {
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->segments = [
      'segment_1' => $this->segmentRepository->createOrUpdate('Segment 1'),
      'segment_2' => $this->segmentRepository->createOrUpdate('Segment 2'),
    ];
  }

  /**
   * @dataProvider dataForTestTriggeredByAutomationRun
   */
  public function testTriggeredByAutomationRun(array $segmentIndexes, string $currentSegmentIndex, bool $expectation): void {
    $segmentIds = $this->getSegmentIds($segmentIndexes);
    $currentSegmentId = $this->getSegmentId($currentSegmentIndex);

    $testee = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $stepRunArgs = new StepRunArgs(
      $this->make(
        Automation::class,
        [
          'getId' => 1,
        ]
      ),
      $this->make(
        AutomationRun::class,
        [
          'getSubjectHash' => 'hash',
        ]
      ),
      new Step('test-id', 'trigger', 'test:trigger', ['segment_ids' => $segmentIds], []),
      [
        new SubjectEntry(
          $this->diContainer->get(SegmentSubject::class),
          new Subject('mailpoet:segment', ['segment_id' => $currentSegmentId])
        ),
      ]
    );
    $this->assertSame($expectation, $testee->isTriggeredBy($stepRunArgs));
  }

  public function dataForTestTriggeredByAutomationRun(): array {
    return [
      'any_list' => [
        [], // any list
        'segment_1',
        true,
      ],
      'list_match' => [
        ['segment_1'],
        'segment_1',
        true,
      ],
      'list_mismatch' => [
        ['segment_1'],
        'segment_2',
        false,
      ],
    ];
  }

  /**
   * @dataProvider dataForTestItObeysMultipleRunsSetting
   */
  public function testItObeysMultipleRunsSetting(bool $runMultipleTimes, int $expectedRuns) {
    $automation = $this->tester->createAutomation('test',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        SomeoneSubscribesTrigger::KEY,
        [
          'segment_ids' => [],
          'run_multiple_times' => $runMultipleTimes,
        ],
        []
      )
    );

    /** @var SomeoneSubscribesTrigger $testee */
    $testee = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('test@mailpoet.com');
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    $subscriberSegment1 = new SubscriberSegmentEntity($this->segments['segment_1'], $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberSegment2 = new SubscriberSegmentEntity($this->segments['segment_2'], $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $testee->handleSubscription($subscriberSegment1);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $testee->handleSubscription($subscriberSegment2);
    $this->assertCount($expectedRuns, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function dataForTestItObeysMultipleRunsSetting(): array {
    return [
      'run_only_once' => [false, 1],
      'run_more_often' => [true, 2],
    ];
  }

  public function _after() {
    $segmentIds = $this->getSegmentIds(array_keys($this->segments));
    $this->segmentRepository->bulkDelete($segmentIds);
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->subscribersRepository->truncate();
  }

  private function getSegmentId(string $index): int {
    return (int)$this->segments[$index]->getId();
  }

  private function getSegmentIds(array $indexes): array {
    return array_map(function (string $index): int {
      return $this->getSegmentId($index);
    }, $indexes);
  }
}
