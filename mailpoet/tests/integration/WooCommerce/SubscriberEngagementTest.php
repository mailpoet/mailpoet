<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use DateTimeInterface;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

/**
 * @group woo
 */
class SubscriberEngagementTest extends \MailPoetTest {
  /** @var Helper & MockObject */
  private $wooCommerceHelperMock;

  /** @var SubscriberEngagement */
  private $subscriberEngagement;

  public function _before() {
    $this->wooCommerceHelperMock = $this->createMock(Helper::class);
    $this->subscriberEngagement = new SubscriberEngagement(
      $this->wooCommerceHelperMock,
      $this->diContainer->get(SubscribersRepository::class),
    );
  }

  public function testItUpdatesLastEngagementForSubscriberWhoCreatedNewOrder() {
    $now = new Carbon('2021-08-31 13:13:00');
    Carbon::setTestNow($now);
    $subscriber = $this->createSubscriber();
    $order = $this->createOrderMock($subscriber->getEmail());
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn($order);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getLastEngagementAt())->equals($now);
    Carbon::setTestNow();
  }

  public function testItDoesntUpdateAnythingForNonExistingOrder() {
    $subscriber = $this->createSubscriber();
    $this->wooCommerceHelperMock
      ->expects($this->once())
      ->method('wcGetOrder')
      ->willReturn(false);
    $this->subscriberEngagement->updateSubscriberEngagement(1);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getLastEngagementAt())->null();
  }

  public function testItUpdatesTimestampsWhenOrderChangesToPaidStatus(): void {
    $subscriber = $this->createSubscriber();
    verify($subscriber->getLastEngagementAt())->null();
    verify($subscriber->getLastPurchaseAt())->null();
    $order = $this->tester->createWooCommerceOrder(['status' => 'pending', 'billing_email' => $subscriber->getEmail()]);
    $order->set_status('processing');
    $order->save();
    $engagementTime = $subscriber->getLastEngagementAt();
    $purchaseTime = $subscriber->getLastPurchaseAt();
    $this->assertInstanceOf(DateTimeInterface::class, $engagementTime);
    $this->assertInstanceOf(DateTimeInterface::class, $purchaseTime);
    verify($engagementTime)->equals($purchaseTime);
    verify($engagementTime)->greaterThan(Carbon::now()->subMinute());
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
    $subscriber->setEmail('subscriber@engagement.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }
}
