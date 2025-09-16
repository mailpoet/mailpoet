<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\SubjectTransformers;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;

class CustomerSubjectToSubscriberSubjectTransformer implements SubjectTransformer {

  /** @var SubscribersRepository  */
  private $subscribersRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
  }

  public function transform(Subject $data): Subject {
    if ($this->accepts() !== $data->getKey()) {
      throw new \InvalidArgumentException('Invalid subject type');
    }

      $subscriber = $this->findOrCreateSubscriber($data);
    if (!$subscriber instanceof SubscriberEntity) {
      throw new \InvalidArgumentException('Subscriber not found');
    }

    return new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]);
  }

  public function accepts(): string {
    return CustomerSubject::KEY;
  }

  public function returns(): string {
    return SubscriberSubject::KEY;
  }

  private function findOrCreateSubscriber(Subject $customer): ?SubscriberEntity {
    $subscriber = $this->findSubscriber($customer);
    if ($subscriber) {
      return $subscriber;
    }

    return null;
  }

  private function findSubscriber(Subject $customer): ?SubscriberEntity {
    $customerId = $customer->getArgs()['customer_id'] ?? null;
    if (!$customerId) {
      return null;
    }

    // Customer ID is equal to WP user ID.
    return $this->subscribersRepository->findOneBy(['wpUserId' => $customerId]);
  }
}
