<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\Subscribers\SubscribersRepository;

class SubscriberSubject implements Subject {
  const KEY = 'mailpoet:subscriber';

  /** @var Field[] */
  private $fields;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberEntity|null */
  private $subscriber;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;

    $this->fields = [
      'id' => new Field(
        'mailpoet:subscriber:id',
        Field::TYPE_INTEGER,
        __('Subscriber ID', 'mailpoet'),
        function() {
          return $this->getSubscriber()->getId();
        }
      ),

      'email' => new Field(
        'mailpoet:subscriber:email',
        Field::TYPE_STRING,
        __('Subscriber email', 'mailpoet'),
        function () {
          return $this->getSubscriber()->getEmail();
        }
      ),

      'status' => new Field(
        'mailpoet:subscriber:status',
        Field::TYPE_ENUM,
        __('Subscriber status', 'mailpoet'),
        function () {
          return $this->getSubscriber()->getStatus();
        },
        [
          SubscriberEntity::STATUS_SUBSCRIBED => __('Subscribed', 'mailpoet'),
          SubscriberEntity::STATUS_UNCONFIRMED => __('Unconfirmed', 'mailpoet'),
          SubscriberEntity::STATUS_UNSUBSCRIBED => __('Unsubscribed', 'mailpoet'),
          SubscriberEntity::STATUS_INACTIVE => __('Inactive', 'mailpoet'),
          SubscriberEntity::STATUS_BOUNCED => __('Bounced', 'mailpoet'),
        ]
      ),
    ];
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getFields(): array {
    return $this->fields;
  }

  public function load(array $args): void {
    $id = $args['subscriber_id'];
    $this->subscriber = $this->subscribersRepository->findOneById($id);
    if (!$this->subscriber) {
      // translators: %d is the ID.
      throw NotFoundException::create()->withMessage(__(sprintf("Subscriber with ID '%d' not found.", $id), 'mailpoet'));
    }
  }

  public function pack(): array {
    $subscriber = $this->getSubscriber();
    return ['subscriber_id' => $subscriber->getId()];
  }

  public function getSubscriber(): SubscriberEntity {
    if (!$this->subscriber) {
      throw InvalidStateException::create()->withMessage(__('Subscriber was not loaded.', 'mailpoet'));
    }
    return $this->subscriber;
  }
}
