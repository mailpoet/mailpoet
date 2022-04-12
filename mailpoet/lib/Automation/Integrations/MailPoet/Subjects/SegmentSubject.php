<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Workflows\Field;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsRepository;

class SegmentSubject implements Subject {
  /** @var Field[] */
  private $fields;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentEntity|null */
  private $segment;

  public function __construct(
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;

    $this->fields = [
      'name' => 
      new Field(
        'mailpoet:segment:name',
        Field::TYPE_STRING,
        __('Segment name', 'mailpoet'),
        function () {
          return $this->getSegment()->getName();
        }
      ),
      'id' => 
      new Field(
        'mailpoet:segment:id',
        Field::TYPE_INTEGER,
        __('Segment ID', 'mailpoet'),
        function () {
          return $this->getSegment()->getId();
        }
      ),
    ];
  }

  public function getKey(): string {
    return 'mailpoet:segment';
  }

  public function getFields(): array {
    return $this->fields;
  }

  public function load(array $args): void {
    $id = $args['segment_id'];
    $this->segment = $this->segmentsRepository->findOneById($args['segment_id']);
    if (!$this->segment) {
      throw NotFoundException::create()->withMessage(__(sprintf("Segment with ID '%s' not found.", $id), 'mailpoet'));
    }
  }

  public function pack(): array {
    $segment = $this->getSegment();
    return ['segment_id' => $segment->getId()];
  }

  private function getSegment(): SegmentEntity {
    if (!$this->segment) {
      throw InvalidStateException::create()->withMessage(__('Segment was not loaded.', 'mailpoet'));
    }
    return $this->segment;
  }
}
