<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Entities\SegmentEntity;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsFinder;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * @implements Subject<SubscriberPayload>
 */
class SubscriberSubject implements Subject {
  const KEY = 'mailpoet:subscriber';

  /** @var SegmentsFinder */
  private $segmentsFinder;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SegmentsFinder $segmentsFinder,
    SegmentsRepository $segmentsRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->segmentsFinder = $segmentsFinder;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    return __('MailPoet subscriber', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'subscriber_id' => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $id = $subjectData->getArgs()['subscriber_id'];
    $subscriber = $this->subscribersRepository->findOneById($id);
    if (!$subscriber) {
      // translators: %d is the ID.
      throw NotFoundException::create()->withMessage(sprintf(__("Subscriber with ID '%d' not found.", 'mailpoet'), $id));
    }
    return new SubscriberPayload($subscriber);
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      // phpcs:disable Squiz.PHP.CommentedOutCode.Found -- temporarily hide those fields
      /*
      new Field(
        'mailpoet:subscriber:id',
        Field::TYPE_INTEGER,
        __('Subscriber ID', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getId();
        }
      ),
      new Field(
        'mailpoet:subscriber:email',
        Field::TYPE_STRING,
        __('Subscriber email', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getEmail();
        }
      ),
      new Field(
        'mailpoet:subscriber:status',
        Field::TYPE_ENUM,
        __('Subscriber status', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getStatus();
        },
        [
          'options' => [
            SubscriberEntity::STATUS_SUBSCRIBED => __('Subscribed', 'mailpoet'),
            SubscriberEntity::STATUS_UNCONFIRMED => __('Unconfirmed', 'mailpoet'),
            SubscriberEntity::STATUS_UNSUBSCRIBED => __('Unsubscribed', 'mailpoet'),
            SubscriberEntity::STATUS_BOUNCED => __('Bounced', 'mailpoet'),
          ],
        ]
      ),
      */
      new Field(
        'mailpoet:subscriber:segments',
        Field::TYPE_ENUM_ARRAY,
        __('Subscriber segments', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $segments = $this->segmentsFinder->findDynamicSegments($payload->getSubscriber());
          $value = [];
          foreach ($segments as $segment) {
            $value[] = $segment->getId();
          }
          return $value;
        },
        [
          'options' => array_map(function ($segment) {
            return [
              'id' => $segment->getId(),
              'name' => $segment->getName(),
            ];
          }, $this->segmentsRepository->findBy(['type' => SegmentEntity::TYPE_DYNAMIC])),
        ]
      ),
    ];
  }
}
