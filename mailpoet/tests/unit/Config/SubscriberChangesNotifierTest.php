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

  public function testItNotifyCreatedSubscriberId(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(1234);
    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 6)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(6);
    $notifier->notify();
  }

  public function testItNotifyMultipleSubscribersCreated(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('currentTime')
      ->willReturn(1234);
    $this->wpFunctions->expects($this->at(1))
      ->method('currentTime')
      ->willReturn(3456);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_CREATED, 1234)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(4);
    $notifier->subscriberCreated(6);
    $notifier->notify();
  }

  public function testItNotifyUpdatedSubscriberId(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(4567);
    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 2)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberUpdated(2);
    $notifier->notify();
  }

  public function testItNotifyMultipleSubscribersUpdated(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('currentTime')
      ->willReturn(12345);
    $this->wpFunctions->expects($this->at(1))
      ->method('currentTime')
      ->willReturn(1234);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_UPDATED, 1234)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberUpdated(2);
    $notifier->subscriberUpdated(41);
    $notifier->notify();
  }

  public function testItNotifyDeletedSubscriberId(): void {
    $this->wpFunctions->method('currentTime')
      ->willReturn(3456);
    $this->wpFunctions->expects($this->at(1))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_DELETED, 1)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberDeleted(1);
    $notifier->notify();
  }

  public function testItNotifyMultipleSubscribersDeleted(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('currentTime')
      ->willReturn(3456);
    $this->wpFunctions->expects($this->at(1))
      ->method('currentTime')
      ->willReturn(98712);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_DELETED, [1, 12])
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
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 1)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(4))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_UPDATED, 3)
      ->willReturn(true);

    $this->wpFunctions->expects($this->at(5))
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
    $this->wpFunctions->method('currentTime')
      ->willReturn(1235);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_SUBSCRIBER_CREATED, 11)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscriberCreated(11);
    $notifier->subscriberUpdated(11);
    $notifier->notify();
  }

  public function testItNotifySubscribersBatchCreate(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('currentTime')
      ->willReturn(3456);
    $this->wpFunctions->expects($this->at(1))
      ->method('currentTime')
      ->willReturn(98712);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_CREATED, 3456)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscribersBatchCreate();
    $notifier->subscribersBatchCreate();
    $notifier->notify();
  }

  public function testItNotifySubscribersBatchUpdate(): void {
    $this->wpFunctions->expects($this->at(0))
      ->method('currentTime')
      ->willReturn(1234);
    $this->wpFunctions->expects($this->at(1))
      ->method('currentTime')
      ->willReturn(123);
    $this->wpFunctions->expects($this->at(2))
      ->method('doAction')
      ->with(SubscriberEntity::HOOK_MULTIPLE_SUBSCRIBERS_UPDATED, 123)
      ->willReturn(true);

    $notifier = new SubscriberChangesNotifier($this->wpFunctions);
    $notifier->subscribersBatchUpdate();
    $notifier->subscribersBatchUpdate();
    $notifier->notify();
  }
}
