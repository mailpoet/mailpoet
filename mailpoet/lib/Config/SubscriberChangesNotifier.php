<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberChangesNotifier {

  /** @var int[] */
  private $createdSubscriberIds = [];

  /** @var int[] */
  private $deletedSubscriberIds = [];

  /** @var int[] */
  private $updatedSubscriberIds = [];

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function notify() {
    $this->notifyCreations();
    $this->notifyUpdates();
    $this->notifyDeletes();
  }

  private function notifyCreations(): void {
    foreach ($this->createdSubscriberIds as $subscriberId) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, $subscriberId);
    }
  }

  private function notifyUpdates(): void {
    foreach ($this->updatedSubscriberIds as $subscriberId) {
      // do not notify about changes when subscriber is new
      if (in_array($subscriberId, $this->createdSubscriberIds, true)) {
        continue;
      }
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, $subscriberId);
    }
  }

  private function notifyDeletes(): void {
    foreach ($this->deletedSubscriberIds as $subscriberId) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, $subscriberId);
    }
  }

  public function subscriberCreated(int $subscriberId): void {
    // to avoid duplicities we use id as a key
    $this->createdSubscriberIds[$subscriberId] = $subscriberId;
  }

  public function subscriberUpdated(int $subscriberId): void {
    // to avoid duplicities we use id as a key
    $this->updatedSubscriberIds[$subscriberId] = $subscriberId;
  }

  public function subscriberDeleted(int $subscriberId): void {
    // to avoid duplicities we use id as a key
    $this->deletedSubscriberIds[$subscriberId] = $subscriberId;
  }
}
