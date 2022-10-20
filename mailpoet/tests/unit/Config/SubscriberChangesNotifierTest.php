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
    $this->wpFunctions->method('currentTime')
      ->willReturn(1234);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 6, 1234)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(3))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 10, 1234)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(6);
    $notifier->subscriberCreated(10);
    $notifier->notify();
  }

  public function testItNotifyUpdatedSubscriberIds(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(4567);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 2, 4567)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(3))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 11, 4567)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberUpdated(2);
    $notifier->subscriberUpdated(11);
    $notifier->notify();
  }

  public function testItNotifyDeletedSubscriberIds(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(3456);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 1, 3456)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(3))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 12, 3456)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberDeleted(1);
    $notifier->subscriberDeleted(12);
    $notifier->notify();
  }

  public function testItNotifyDifferentSubscriberChanges(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(12345);
    $this->wpFunctions->expects($this->at(3))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 1, 12345)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(4))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 3, 12345)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(5))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 5, 12345)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberDeleted(5);
    $notifier->subscriberUpdated(3);
    $notifier->subscriberCreated(1);
    $notifier->notify();
  }

  public function testItNotifyUpdateForCreatedSubscriber(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(1235);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 11, 1235)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(11);
    $notifier->subscriberUpdated(11);
    $notifier->notify();
  }
}
