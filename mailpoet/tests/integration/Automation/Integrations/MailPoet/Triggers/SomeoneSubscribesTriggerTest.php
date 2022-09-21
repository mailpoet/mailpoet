<?php

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class SomeoneSubscribesTriggerTest extends \MailPoetTest
{

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
  public function testTriggeredByWorkflowRun(array $segmentSetting, string $currentSegmentId, bool $expectation) {
    /** @var SomeoneSubscribesTrigger $testee */
    $testee = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $args = [
      'segment_ids' => array_map(
        function($key) {
          return isset($this->segments[$key]) ? $this->segments[$key]->getId() : $key;
        },
        $segmentSetting
      )
    ];
    /** @var SegmentSubject $segmentSubject */
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $this->segments[$currentSegmentId]->getId()]);
    $this->assertSame($expectation, $testee->isTriggeredBy($args, $segmentSubject));
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
