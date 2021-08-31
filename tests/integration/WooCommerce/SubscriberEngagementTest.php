<?php

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

class SubscriberEngagementTest extends \MailPoetTest {

  /** @var Helper & MockObject */
  private $wooCommerceHelperMock;

  /** @var SubscriberEngagement */
  private $subscriberEngagement;

  public function _before() {
    $this->wooCommerceHelperMock = $this->createMock(Helper::class);
    $this->subscriberEngagement = new SubscriberEngagement(
      $this->wooCommerceHelperMock,
      $this->diContainer->get(SubscribersRepository::class)
    );
    $this->truncateEntity(SubscriberEntity::class);
  }

  public function testItUpdatesLastEngagementForSubscriberWhoCreatedNewOrder() {
    Carbon::setTestNow($now = new Carbon('2021-08-31 13:13:00'));
    $subscriber = $this->createSubscriber();
    $order = $this->createOrderMock($subscriber->getEmail());
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn($order);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($now);
    Carbon::setTestNow();
  }

  public function testItDoesntUpdateAnythingForNonExistingOder() {
    $subscriber = $this->createSubscriber();
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn(false);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->null();
  }

  public function testItDoesntThrowAnErrorForNonExistingSubscriber() {
    $order = $this->createOrderMock('some@email.com');
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn($order);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
  }

  private function createOrderMock($email) {
    $mock = $this->getMockBuilder(WC_Order::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_billing_email'])
      ->getMock();

    $mock->method('get_billing_email')->willReturn($email);
    return $mock;
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('subscriber@egagement.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }
}
