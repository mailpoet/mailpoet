<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\EventListeners;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\Event\LifecycleEventArgs;

class NewsletterListener {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function prePersist(): void {
    // the tests fail without this method
  }

  public function preUpdate(LifecycleEventArgs $eventArgs): void {
    $entity = $eventArgs->getEntity();
    if (!$entity instanceof NewsletterEntity) {
      return;
    }

    $unitOfWork = $eventArgs->getEntityManager()->getUnitOfWork();

    /** @var array{status: array{0: string, 1: string}} $changeSet */
    $changeSet = $unitOfWork->getEntityChangeSet($entity);
    if (!isset($changeSet['status'])) {
      return;
    }

    [$oldStatus, $newStatus] = $changeSet['status'];

    if ($oldStatus !== NewsletterEntity::STATUS_SENT && $newStatus === NewsletterEntity::STATUS_SENT) {
      $post = $entity->getWpPost();
      if ($post instanceof WpPostEntity) {
        $this->wp->wpUpdatePost([
          'ID' => $post->getId(),
          'post_status' => NewsletterEntity::STATUS_SENT,
        ]);
      }
    }
  }
}
