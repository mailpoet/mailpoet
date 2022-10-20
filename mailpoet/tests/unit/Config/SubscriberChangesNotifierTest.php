<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class SubscriberChangesNotifierTest extends \MailPoetUnitTest {

  /** @var WPFunctions & MockObject */
  private $wpFunctions;

  public function _before() {
    parent::_before();
    $this->wpFunctions = $this->createMock(WPFunctions::class);
  }

  public function testItNotifyCreatedSubscriberIds(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 6)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 10)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(6);
    $notifier->subscriberCreated(10);
    $notifier->notify();
  }

  public function testItNotifyUpdatedSubscriberIds(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 2)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 11)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberUpdated(2);
    $notifier->subscriberUpdated(11);
    $notifier->notify();
  }

  public function testItNotifyDeletedSubscriberIds(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 1)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 12)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberDeleted(1);
    $notifier->subscriberDeleted(12);
    $notifier->notify();
  }

  public function testItNotifyDifferentSubscriberChanges(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 1)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 3)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 5)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberDeleted(5);
    $notifier->subscriberUpdated(3);
    $notifier->subscriberCreated(1);
    $notifier->notify();
  }

  public function testItNotifyUpdateForCreatedSubscriber(): void {
    $this->wpFunctions->expects($this->once())
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 11)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(11);
    $notifier->subscriberUpdated(11);
    $notifier->notify();
  }
}
