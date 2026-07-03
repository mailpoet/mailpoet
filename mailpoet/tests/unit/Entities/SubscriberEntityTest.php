<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoetVendor\Carbon\Carbon;

class SubscriberEntityTest extends \MailPoetUnitTest {
  public function testMarkEngagedReactivatesInactiveSubscriber(): void {
    $now = new Carbon();
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_INACTIVE);

    $subscriber->markEngaged($now);

    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriber->getLastEngagementAt())->equals($now);
  }

  /** @dataProvider dataProviderNonInactiveStatuses */
  public function testMarkEngagedKeepsNonInactiveStatus(string $status): void {
    $now = new Carbon();
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus($status);

    $subscriber->markEngaged($now);

    verify($subscriber->getStatus())->equals($status);
    verify($subscriber->getLastEngagementAt())->equals($now);
  }

  public function dataProviderNonInactiveStatuses(): array {
    return [
      [SubscriberEntity::STATUS_SUBSCRIBED],
      [SubscriberEntity::STATUS_UNCONFIRMED],
      [SubscriberEntity::STATUS_UNSUBSCRIBED],
      [SubscriberEntity::STATUS_BOUNCED],
    ];
  }
}
