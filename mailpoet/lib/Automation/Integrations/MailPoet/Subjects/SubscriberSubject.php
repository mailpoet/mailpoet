<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsFinder;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tags\TagRepository;
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

  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    SegmentsFinder $segmentsFinder,
    SegmentsRepository $segmentsRepository,
    SubscribersRepository $subscribersRepository,
    TagRepository $tagRepository
  ) {
    $this->segmentsFinder = $segmentsFinder;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->tagRepository = $tagRepository;
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
      new Field(
        'mailpoet:subscriber:email',
        Field::TYPE_STRING,
        __('Email address', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getEmail();
        }
      ),
      new Field(
        'mailpoet:subscriber:engagement-score',
        Field::TYPE_NUMBER,
        __('Engagement score', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getEngagementScore();
        }
      ),
      new Field(
        'mailpoet:subscriber:first-name',
        Field::TYPE_STRING,
        __('First name', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getFirstName();
        }
      ),
      new Field(
        'mailpoet:subscriber:last-name',
        Field::TYPE_STRING,
        __('Last name', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getLastName();
        }
      ),
      new Field(
        'mailpoet:subscriber:is-globally-subscribed',
        Field::TYPE_BOOLEAN,
        __('Is globally subscribed', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED;
        }
      ),
      new Field(
        'mailpoet:subscriber:last-engagement-at',
        Field::TYPE_DATETIME,
        __('Last engaged', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getLastEngagementAt();
        }
      ),
      new Field(
        'mailpoet:subscriber:status',
        Field::TYPE_ENUM,
        __('Status', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getStatus();
        },
        [
          'options' => [
            [
              'id' => SubscriberEntity::STATUS_SUBSCRIBED,
              'name' => __('Subscribed', 'mailpoet'),
            ],
            [
              'id' => SubscriberEntity::STATUS_UNCONFIRMED,
              'name' => __('Unconfirmed', 'mailpoet'),
            ],
            [
              'id' => SubscriberEntity::STATUS_UNSUBSCRIBED,
              'name' => __('Unsubscribed', 'mailpoet'),
            ],
            [
              'id' => SubscriberEntity::STATUS_INACTIVE,
              'name' => __('Inactive', 'mailpoet'),
            ],
            [
              'id' => SubscriberEntity::STATUS_BOUNCED,
              'name' => __('Bounced', 'mailpoet'),
            ],
          ],
        ]
      ),
      new Field(
        'mailpoet:subscriber:subscription-source',
        Field::TYPE_ENUM,
        __('Subscription source', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getSource();
        },
        [
          'options' => [
            [
              'id' => 'api',
              'name' => __('API', 'mailpoet'),
            ],
            [
              'id' => 'form',
              'name' => __('Form', 'mailpoet'),
            ],
            [
              'id' => 'unknown',
              'name' => __('Unknown', 'mailpoet'),
            ],
            [
              'id' => 'imported',
              'name' => __('Imported', 'mailpoet'),
            ],
            [
              'id' => 'administrator',
              'name' => __('Administrator', 'mailpoet'),
            ],
            [
              'id' => 'wordpress_user',
              'name' => __('WordPress user', 'mailpoet'),
            ],
            [
              'id' => 'woocommerce_user',
              'name' => __('WooCommerce user', 'mailpoet'),
            ],
            [
              'id' => 'woocommerce_checkout',
              'name' => __('WooCommerce checkout', 'mailpoet'),
            ],
          ],
        ]
      ),
      new Field(
        'mailpoet:subscriber:last-subscribed-at',
        Field::TYPE_DATETIME,
        __('Subscribed date', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getLastSubscribedAt();
        }
      ),
      new Field(
        'mailpoet:subscriber:tags',
        Field::TYPE_ENUM_ARRAY,
        __('Tags', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $value = [];
          foreach ($payload->getSubscriber()->getSubscriberTags() as $subscriberTag) {
            $tag = $subscriberTag->getTag();
            if ($tag) {
              $value[] = $tag->getId();
            }
          }
          return $value;
        },
        [
          'options' => array_map(function ($tag) {
            return [
              'id' => $tag->getId(),
              'name' => $tag->getName(),
            ];
          }, $this->tagRepository->findAll()),
        ]
      ),
      new Field(
        'mailpoet:subscriber:segments',
        Field::TYPE_ENUM_ARRAY,
        __('Segments', 'mailpoet'),
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
      new Field(
        'mailpoet:subscriber:email-sent-count',
        Field::TYPE_INTEGER,
        __('Email — sent count', 'mailpoet'),
        function (SubscriberPayload $payload) {
          return $payload->getSubscriber()->getEmailCount();
        }
      ),
    ];
  }
}
