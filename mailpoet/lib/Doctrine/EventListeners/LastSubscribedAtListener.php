<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Event\LifecycleEventArgs;

class LastSubscribedAtListener {
  public function prePersist(LifecycleEventArgs $eventArgs): void {
    $entity = $eventArgs->getEntity();

    if ($entity instanceof SubscriberEntity && $entity->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
      $entity->setLastSubscribedAt(Carbon::now()->millisecond(0));
    }
  }

  public function preUpdate(LifecycleEventArgs $eventArgs): void {
    $entity = $eventArgs->getEntity();
    if (!$entity instanceof SubscriberEntity) {
      return;
    }

    $unitOfWork = $eventArgs->getEntityManager()->getUnitOfWork();
    $changeSet = $unitOfWork->getEntityChangeSet($entity);
    if (!isset($changeSet['status'])) {
      return;
    }

    [$oldStatus, $newStatus] = $changeSet['status'];
    // Update last_subscribed_at when status changes to subscribed
    if ($oldStatus !== SubscriberEntity::STATUS_SUBSCRIBED && $newStatus === SubscriberEntity::STATUS_SUBSCRIBED) {
      $entity->setLastSubscribedAt(Carbon::now()->millisecond(0));
    }
  }
}
