<?php

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class SegmentSubscribedTriggerTest extends \MailPoetTest
{

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SegmentEntity[] */
  private $segments;

  /** @var WorkflowStorage */
  private $workflowRepository;
  public function _before() {
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->segments = [
        'segment_1' => $this->segmentRepository->createOrUpdate('Segment 1'),
        'segment_2' => $this->segmentRepository->createOrUpdate('Segment 2'),
    ];
    $this->workflowRepository = $this->diContainer->get(WorkflowStorage::class);
  }

  /**
   * @dataProvider dataForTestTriggeredByWorkflowRun
   */
  public function testTriggeredByWorkflowRun(array $segmentSetting, string $currentSegmentId, bool $expectation) {
    /** @var SegmentSubscribedTrigger $testee */
    $testee = $this->diContainer->get(SegmentSubscribedTrigger::class);
    $workflow = new Workflow(
      'test',
      [
        Step::fromArray([
          'id' => '1',
          'name' => 'TestData',
          'key' => $testee->getKey(),
          'type' => Step::TYPE_TRIGGER,
          'next_steps' => [],
          'args' => [
            'segment_ids' => array_map(
              function($key) {
                return isset($this->segments[$key]) ? $this->segments[$key]->getId() : $key;
              },
              $segmentSetting
            )
          ],
        ])
      ],
      new \WP_User()
    );
    $workflowId = $this->workflowRepository->createWorkflow($workflow);
    $workflow = $this->workflowRepository->getWorkflow($workflowId);
    self::assertInstanceOf(Workflow::class, $workflow);

    /** @var SegmentSubject $segmentSubject */
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $this->segments[$currentSegmentId]->getId()]);
    $run = new WorkflowRun($workflow->getId(), $workflow->getVersionId(),$testee->getKey(),[$segmentSubject]);
    $this->assertSame($expectation, $testee->isTriggeredBy($run));
  }

  public function dataForTestTriggeredByWorkflowRun() : array {
    return [
      'any_list' => [
        [], //Any list setting
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
    $segmentIds = array_values(array_map(
      function(SegmentEntity $seg) : int { return (int)$seg->getId(); },
      $this->segments
    ));
    $this->segmentRepository->bulkDelete(
      $segmentIds
    );
  }
}
