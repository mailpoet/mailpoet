<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscriberChangesNotifier {

  /** @var array<int, Carbon> */
  private $createdSubscriberIds = [];

  /** @var array<int, Carbon> */
  private $deletedSubscriberIds = [];

  /** @var array<int, Carbon> */
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
    foreach ($this->createdSubscriberIds as $subscriberId => $createdAt) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, $subscriberId, $createdAt->getTimestamp());
    }
  }

  private function notifyUpdates(): void {
    foreach ($this->updatedSubscriberIds as $subscriberId => $updatedAt) {
      // do not notify about changes when subscriber is new
      if (isset($this->createdSubscriberIds[$subscriberId])) {
        continue;
      }
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, $subscriberId, $updatedAt->getTimestamp());
    }
  }

  private function notifyDeletes(): void {
    foreach ($this->deletedSubscriberIds as $subscriberId => $deletedAt) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, $subscriberId, $deletedAt->getTimestamp());
    }
  }

  public function subscriberCreated(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $this->createdSubscriberIds[$subscriberId] = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'), 'UTC');
  }

  public function subscriberUpdated(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $this->updatedSubscriberIds[$subscriberId] = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'), 'UTC');
  }

  public function subscriberDeleted(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $this->deletedSubscriberIds[$subscriberId] = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'), 'UTC');
  }
}
