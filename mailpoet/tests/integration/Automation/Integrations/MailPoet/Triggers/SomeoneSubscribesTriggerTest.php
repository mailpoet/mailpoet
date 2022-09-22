<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class SomeoneSubscribesTriggerTest extends \MailPoetTest {
  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SegmentEntity[] */
  private $segments;

  public function _before() {
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->segments = [
      'segment_1' => $this->segmentRepository->createOrUpdate('Segment 1'),
      'segment_2' => $this->segmentRepository->createOrUpdate('Segment 2'),
    ];
  }

  /**
   * @dataProvider dataForTestTriggeredByWorkflowRun
   */
  public function testTriggeredByWorkflowRun(array $segmentIndexes, string $currentSegmentIndex, bool $expectation): void {
    $segmentIds = $this->getSegmentIds($segmentIndexes);
    $currentSegmentId = $this->getSegmentId($currentSegmentIndex);

    $testee = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $stepRunArgs = new StepRunArgs(
      $this->make(Workflow::class),
      $this->make(WorkflowRun::class),
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

  public function dataForTestTriggeredByWorkflowRun(): array {
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

  public function _after() {
    $segmentIds = $this->getSegmentIds(array_keys($this->segments));
    $this->segmentRepository->bulkDelete($segmentIds);
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
