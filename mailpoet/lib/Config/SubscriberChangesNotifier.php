<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscriberChangesNotifier {

  /** @var array<int, int> */
  private $createdSubscriberIds = [];

  /** @var array<int, int> */
  private $deletedSubscriberIds = [];

  /** @var array<int, int> */
  private $updatedSubscriberIds = [];

  /** @var array<int, int> */
  private $statusChangedSubscriberIds = [];

  /** @var array<int, int> */
  private $countChangedSubscriberIds = [];

  /**
   * Consent changes, keyed by subscriber id. Deliberately not collapsed into a batch the
   * way updates are: a consent change is a per-person legal record, so every one is
   * delivered on its own.
   *
   * @var array<int, array{0: string, 1: string}>
   */
  private $trackingConsentChanges = [];

  /** @var array<int, int> */
  private $createdSubscriberBatches = [];

  /** @var array<int, int> */
  private $updatedSubscriberBatches = [];

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
    $this->notifyCountChanges();
    $this->notifyTrackingConsentChanges();
  }

  public function subscriberTrackingConsentChanged(int $subscriberId, string $oldConsent, string $newConsent): void {
    $this->trackingConsentChanges[$subscriberId] = [$oldConsent, $newConsent];
  }

  private function notifyTrackingConsentChanges(): void {
    foreach ($this->trackingConsentChanges as $subscriberId => list($oldConsent, $newConsent)) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_TRACKING_CONSENT_CHANGED, $subscriberId, $oldConsent, $newConsent);
    }
  }

  private function notifyCreations(): void {
    if (count($this->createdSubscriberIds) > 1) {
      $minTimestamp = min($this->createdSubscriberIds);
      if ($minTimestamp) {
        $this->createdSubscriberBatches[] = $minTimestamp;
        $this->createdSubscriberIds = []; // reset created subscribers
      }
    }

    foreach ($this->createdSubscriberIds as $subscriberId => $updatedAt) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, $subscriberId);
    }

    if ($this->createdSubscriberBatches) {
      $minTimestamp = min($this->createdSubscriberBatches);
      if ($minTimestamp) {
        $this->wp->doAction(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_CREATED, $minTimestamp);
      }
    }
  }

  private function notifyUpdates(): void {
    // unset updated subscribers if subscriber is created
    foreach ($this->createdSubscriberIds as $subscriberId => $timestamp) {
      unset($this->updatedSubscriberIds[$subscriberId]);
      unset($this->statusChangedSubscriberIds[$subscriberId]);
    }

    if (count($this->updatedSubscriberIds) > 1) {
      $minTimestamp = min($this->updatedSubscriberIds);
      if ($minTimestamp) {
        $this->updatedSubscriberBatches[] = $minTimestamp;
        $this->updatedSubscriberIds = []; // reset updated subscribers
        $this->statusChangedSubscriberIds = []; // reset status changed subscribers
      }
    }

    foreach ($this->updatedSubscriberIds as $subscriberId => $updatedAt) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, $subscriberId);
    }

    foreach ($this->statusChangedSubscriberIds as $subscriberId => $updatedAt) {
      $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_STATUS_CHANGED, $subscriberId);
    }

    if ($this->updatedSubscriberBatches) {
      $minTimestamp = min($this->updatedSubscriberBatches);
      if ($minTimestamp) {
        $this->wp->doAction(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_UPDATED, $minTimestamp);
      }
    }
  }

  private function notifyDeletes(): void {
    if (count($this->deletedSubscriberIds) === 1) {
      foreach ($this->deletedSubscriberIds as $subscriberId => $updatedAt) {
        $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, $subscriberId);
      }
    } elseif ($this->deletedSubscriberIds) {
      $this->wp->doAction(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_DELETED, array_keys($this->deletedSubscriberIds));
    }
  }

  private function notifyCountChanges(): void {
    if (empty($this->countChangedSubscriberIds)) {
      return;
    }

    $this->wp->doAction(SubscriberEntity::HOOK_SUBSCRIBERS_COUNT_CHANGED, array_keys($this->countChangedSubscriberIds));
  }

  public function subscriberCreated(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $timestamp = $this->getTimestamp();
    $this->createdSubscriberIds[$subscriberId] = $timestamp;
    $this->countChangedSubscriberIds[$subscriberId] = $timestamp;
  }

  public function subscriberUpdated(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $this->updatedSubscriberIds[$subscriberId] = $this->getTimestamp();
  }

  public function subscriberStatusChanged(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $timestamp = $this->getTimestamp();
    $this->statusChangedSubscriberIds[$subscriberId] = $timestamp;
    $this->countChangedSubscriberIds[$subscriberId] = $timestamp;
  }

  public function subscriberDeleted(int $subscriberId): void {
    // store id as a key and timestamp change as the value
    $timestamp = $this->getTimestamp();
    $this->deletedSubscriberIds[$subscriberId] = $timestamp;
    $this->countChangedSubscriberIds[$subscriberId] = $timestamp;
  }

  public function subscribersCreated(array $subscriberIds): void {
    foreach ($subscriberIds as $subscriberId) {
      $this->subscriberCreated((int)$subscriberId);
    }
  }

  public function subscribersUpdated(array $subscriberIds): void {
    foreach ($subscriberIds as $subscriberId) {
      $this->subscriberUpdated((int)$subscriberId);
    }
  }

  public function subscribersDeleted(array $subscriberIds): void {
    foreach ($subscriberIds as $subscriberId) {
      $this->subscriberDeleted((int)$subscriberId);
    }
  }

  public function subscriberCountChanged(int $subscriberId): void {
    $this->countChangedSubscriberIds[$subscriberId] = $this->getTimestamp();
  }

  public function subscribersCountChanged(array $subscriberIds): void {
    foreach ($subscriberIds as $subscriberId) {
      $this->subscriberCountChanged((int)$subscriberId);
    }
  }

  public function subscribersBatchCreate(): void {
    $this->createdSubscriberBatches[] = $this->getTimestamp();
  }

  public function subscribersBatchUpdate(): void {
    $this->updatedSubscriberBatches[] = $this->getTimestamp();
  }

  private function getTimestamp(): int {
    $dateTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp', true), 'UTC');
    return $dateTime->getTimestamp();
  }
}
