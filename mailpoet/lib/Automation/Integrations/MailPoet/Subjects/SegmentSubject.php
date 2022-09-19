<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Workflows\Payload;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * @implements Subject<SegmentPayload>
 */
class SegmentSubject implements Subject {
  const KEY = 'mailpoet:segment';

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    return __('MailPoet segment', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'segment_id' => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $id = $subjectData->getArgs()['segment_id'];
    $segment = $this->segmentsRepository->findOneById($id);
    if (!$segment) {
      // translators: %d is the ID.
      throw NotFoundException::create()->withMessage(sprintf(__("Segment with ID '%d' not found.", 'mailpoet'), $id));
    }
    return new SegmentPayload($segment);
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'mailpoet:segment:id',
        Field::TYPE_INTEGER,
        __('Segment ID', 'mailpoet'),
        function (SegmentPayload $payload) {
          return $payload->getId();
        }
      ),
      new Field(
        'mailpoet:segment:name',
        Field::TYPE_STRING,
        __('Segment name', 'mailpoet'),
        function (SegmentPayload $payload) {
          return $payload->getName();
        }
      ),
    ];
  }
}
