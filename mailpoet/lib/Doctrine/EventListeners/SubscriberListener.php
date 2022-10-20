<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\Persistence\Event\LifecycleEventArgs;

class SubscriberListener {

  /** @var SubscriberChangesNotifier */
  private $subscriberChangesNotifier;

  public function __construct(
    SubscriberChangesNotifier $subscriberChangesNotifier
  ) {
    $this->subscriberChangesNotifier = $subscriberChangesNotifier;
  }

  public function postPersist(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberCreated((int)$subscriber->getId());
  }

  public function postUpdate(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberUpdated((int)$subscriber->getId());
  }

  public function postRemove(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberDeleted((int)$subscriber->getId());
  }
}
