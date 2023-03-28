<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

/**
 * @group woo
 */
class SubscriberEngagementTest extends \MailPoetTest {

  /** @var WPFunctions & MockObject */
  private $wpMock;

  /** @var Helper & MockObject */
  private $wooCommerceHelperMock;

  /** @var SubscriberEngagement */
  private $subscriberEngagement;

  public function _before() {
    $this->wooCommerceHelperMock = $this->createMock(Helper::class);
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->subscriberEngagement = new SubscriberEngagement(
      $this->wooCommerceHelperMock,
      new SubscribersRepository($this->entityManager, new SubscriberChangesNotifier($this->wpMock), $this->wpMock)
    );
  }

  public function testItUpdatesLastEngagementForSubscriberWhoCreatedNewOrder() {
    $now = new Carbon('2021-08-31 13:13:00');
    $this->wpMock->expects($this->once())
      ->method('currentTime')
      ->willReturn($now->getTimestamp());
    $subscriber = $this->createSubscriber();
    $order = $this->createOrderMock($subscriber->getEmail());
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn($order);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->equals($now);
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
