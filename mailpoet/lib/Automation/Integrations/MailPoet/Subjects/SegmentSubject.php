<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Workflows\AbstractSubject;
use MailPoet\Automation\Engine\Workflows\Field;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsRepository;

class SegmentSubject extends AbstractSubject {
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
    ];
  }

  public function getKey(): string {
    return 'mailpoet:segment';
  }

  public function getFields(): array {
    return $this->fields;
  }

  public function getNameField(): Field {
    return $this->getField('name');
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
