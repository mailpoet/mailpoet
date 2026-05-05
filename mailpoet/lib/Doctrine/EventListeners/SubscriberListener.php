<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\Event\LifecycleEventArgs;

class SubscriberListener {

  /** @var SubscriberChangesNotifier */
  private $subscriberChangesNotifier;

  public function __construct(
    SubscriberChangesNotifier $subscriberChangesNotifier
  ) {
    $this->subscriberChangesNotifier = $subscriberChangesNotifier;
  }

  private function maybeNotifyStatusChanged(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $entityManager = $event->getEntityManager();
    $unitOfWork = $entityManager->getUnitOfWork();
    $changeset = $unitOfWork->getEntityChangeSet($subscriber);

    if (array_key_exists('status', $changeset) && $changeset['status'][0] !== $changeset['status'][1]) {
      $this->subscriberChangesNotifier->subscriberStatusChanged((int)$subscriber->getId());
    }
  }

  private function maybeNotifyDeletedAtChanged(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $entityManager = $event->getEntityManager();
    $unitOfWork = $entityManager->getUnitOfWork();
    $changeset = $unitOfWork->getEntityChangeSet($subscriber);

    if (!array_key_exists('deletedAt', $changeset) || $changeset['deletedAt'][0] === $changeset['deletedAt'][1]) {
      return;
    }

    if (!$this->isCountedStatus($subscriber->getStatus())) {
      return;
    }

    $this->subscriberChangesNotifier->subscriberCountChanged((int)$subscriber->getId());
  }

  private function isCountedStatus(string $status): bool {
    return in_array($status, [
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_INACTIVE,
    ], true);
  }

  public function postPersist(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberCreated((int)$subscriber->getId());
  }

  public function postUpdate(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberUpdated((int)$subscriber->getId());
    $this->maybeNotifyStatusChanged($subscriber, $event);
    $this->maybeNotifyDeletedAtChanged($subscriber, $event);
  }

  public function postRemove(SubscriberEntity $subscriber, LifecycleEventArgs $event): void {
    $this->subscriberChangesNotifier->subscriberDeleted((int)$subscriber->getId());
  }
}
