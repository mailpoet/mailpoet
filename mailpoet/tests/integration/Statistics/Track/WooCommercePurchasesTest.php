<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use DateTime;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

/**
 * @group woo
 */
class WooCommercePurchasesTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
  private $queue;

  /** @var NewsletterLinkEntity */
  private $link;

  /** @var Cookies */
  private $cookies;

  /** @var StatisticsWooCommercePurchasesRepository */
  private $statisticsWooCommercePurchasesRepository;

  public function _before() {
    parent::_before();

    $this->subscriber = $this->createSubscriber('test@example.com');
    $this->newsletter = $this->createNewsletter();
    $this->queue = $this->createQueue($this->newsletter, $this->subscriber);
    $this->link = $this->createLink($this->newsletter, $this->queue);
    $this->statisticsWooCommercePurchasesRepository = $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class);
    $this->cookies = new Cookies();
  }

  public function testItTracksOrderRefunds() {

    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($this->subscriber->getEmail());
    $order->set_total('10');
    $order->set_status('completed');
    $order->save();

    $statistic = $this->statisticsWooCommercePurchasesRepository->findOneBy(['orderId' => $order->get_id()]);
    $this->assertInstanceOf(StatisticsWooCommercePurchaseEntity::class, $statistic);
    $this->assertEquals("completed", $statistic->getStatus());
    $this->assertEquals(10, $statistic->getOrderPriceTotal());

    wc_create_refund([
      'order_id' => $order->get_id(),
      'amount' => 2.5,
    ]);
    $this->assertEquals("completed", $statistic->getStatus());
    $this->assertEquals(7.5, $statistic->getOrderPriceTotal());
    wc_create_refund([
      'order_id' => $order->get_id(),
      'amount' => 7.5,
    ]);
    $this->assertEquals("refunded", $statistic->getStatus());
    $this->assertEquals(0, $statistic->getOrderPriceTotal());
  }

  public function testItTracksOrderStatusChanges() {

    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($this->subscriber->getEmail());
    $order->set_total('10');
    $order->set_status('processing');
    $order->save();

    $order2 = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order2);
    $order2->set_billing_email($this->subscriber->getEmail());
    $order2->set_total('12');
    $order2->set_status('processing');
    $order2->save();

    $statistic = $this->statisticsWooCommercePurchasesRepository->findOneBy(['orderId' => $order->get_id()]);
    $this->assertInstanceOf(StatisticsWooCommercePurchaseEntity::class, $statistic);
    $this->assertEquals("processing", $statistic->getStatus());

    $statistic2 = $this->statisticsWooCommercePurchasesRepository->findOneBy(['orderId' => $order2->get_id()]);
    $this->assertInstanceOf(StatisticsWooCommercePurchaseEntity::class, $statistic2);
    $this->assertEquals("processing", $statistic->getStatus());

    $order->set_status('completed');
    $order->save();
    $this->assertEquals("completed", $statistic->getStatus());

    $order2->set_status('completed');
    $order2->save();
    $this->assertEquals("completed", $statistic2->getStatus());
  }

  public function testItDoesNotTrackPaymentForWrongSubscriber() {
    $click = $this->createClick($this->link, $this->subscriber, 3);

    // create 'wrong_click' for different subscriber that is newer than the correct 'click'
    $wrongSubscriber = $this->createSubscriber('wrong.subscriber@example.com');
    $wrongClick = $this->createClick($this->link, $wrongSubscriber, 1);
    $this->entityManager->flush();

    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(1);
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click->getId());
  }

  public function testItTracksPayment() {
    $click = $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(1);
    $newsletter = $purchaseStats[0]->getNewsletter();
    $subscriber = $purchaseStats[0]->getSubscriber();
    $queue = $purchaseStats[0]->getQueue();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($newsletter->getId())->equals($this->newsletter->getId());
    verify($subscriber->getId())->equals($this->subscriber->getId());
    verify($queue->getId())->equals($this->queue->getId());
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click->getId());
    verify($purchaseStats[0]->getOrderId())->equals($orderMock->get_id());
    verify($purchaseStats[0]->getOrderCurrency())->equals($orderMock->get_currency());
    verify($purchaseStats[0]->getOrderPriceTotal())->equals($orderMock->get_remaining_refund_amount());
  }

  public function testItTracksPaymentForMultipleNewsletters() {
    $click1 = $this->createClick($this->link, $this->subscriber);

    // create click in other newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $click2 = $this->createClick($link, $this->subscriber, 1);
    $this->entityManager->flush();

    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(2);

    $stats1 = $this->statisticsWooCommercePurchasesRepository->findOneBy(['newsletter' => $this->newsletter]);
    $this->assertInstanceOf(StatisticsWooCommercePurchaseEntity::class, $stats1);
    $subscriber = $stats1->getSubscriber();
    $queue = $stats1->getQueue();
    $click = $stats1->getClick();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click1->getId());
    verify($subscriber->getId())->equals($this->subscriber->getId());
    verify($queue->getId())->equals($this->queue->getId());

    $stats2 = $this->statisticsWooCommercePurchasesRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertInstanceOf(StatisticsWooCommercePurchaseEntity::class, $stats2);
    $subscriber = $stats2->getSubscriber();
    $queue = $stats2->getQueue();
    $click = $stats2->getClick();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click2->getId());
    verify($subscriber->getId())->equals($this->subscriber->getId());
    verify($queue->getId())->equals($queue->getId());
  }

  public function testItTracksPaymentForMultipleOrders() {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();

    // first order
    $orderMock = $this->createOrderMock($this->subscriber->getEmail(), 10.0, 123);
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    // second order
    $orderMock = $this->createOrderMock($this->subscriber->getEmail(), 20.0, 456);
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(2);
  }

  public function testItTracksPaymentOnlyForLatestClick() {
    $this->createClick($this->link, $this->subscriber, 3);
    $this->createClick($this->link, $this->subscriber, 5);
    $latestClick = $this->createClick($this->link, $this->subscriber, 1);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([], ['createdAt' => 'desc']);
    verify(count($purchaseStats))->equals(1);
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($latestClick->getId());
  }

  public function testItTracksPaymentOnlyOnce() {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(1);
  }

  public function testItTracksPaymentOnlyOnceWhenNewClickAppears() {
    $this->createClick($this->link, $this->subscriber, 5);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());

    $this->createClick($this->link, $this->subscriber, 1);
    $this->entityManager->flush();
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(1);
  }

  public function testItDoesNotTrackPaymentWhenClickTooOld() {
    $this->createClick($this->link, $this->subscriber, 20);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail());
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(0);
  }

  public function testItDoesNotTrackPaymentForDifferentEmail() {
    $this->createClick($this->link, $this->subscriber);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock('different.email@example.com');
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(0);
  }

  public function testItDoesNotTrackPaymentWhenClickNewerThanOrder() {
    $this->createClick($this->link, $this->subscriber, 0);
    $this->entityManager->flush();
    $orderMock = $this->createOrderMock($this->subscriber->getEmail(), 15.0, 123, new DateTime("-1 minute"));
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    verify(count($this->statisticsWooCommercePurchasesRepository->findBy([])))->equals(0);
  }

  public function testItTracksPaymentForCorrectClickWhenClickNewerThanOrderExists() {
    $this->createClick($this->link, $this->subscriber, 5);
    $this->createClick($this->link, $this->subscriber, 0); // wrong click, should not be tracked
    $this->entityManager->flush();

    $orderMock = $this->createOrderMock($this->subscriber->getEmail(), 15.0, 123, new DateTime("-1 minute"));
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify($purchaseStats)->arrayCount(1);
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click->getId());
  }

  public function testItTracksByCookie() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    $click = $this->createClick($this->link, $cookieEmailSubscriber);
    $this->entityManager->flush();
    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $click->getId(),
      'created_at' => time(),
    ]);

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(1);
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($click->getId());
  }

  public function testItDoesNotTrackByCookieWhenTrackedByOrder() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $orderEmailSubscriber = $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    // both clicks are in the same newsletter
    $orderEmailClick = $this->createClick($this->link, $orderEmailSubscriber);
    $cookieEmailClick = $this->createClick($this->link, $cookieEmailSubscriber);

    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookieEmailClick->getId(),
      'created_at' => time(),
    ]);
    $this->entityManager->flush();

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(1);
    $click = $purchaseStats[0]->getClick();
    $this->assertInstanceOf(StatisticsClickEntity::class, $click);
    verify($click->getId())->equals($orderEmailClick->getId());
  }

  public function testItTracksByBothOrderAndCookieForDifferentNewsletters() {
    $orderEmail = 'order.email@example.com';
    $cookieEmail = 'cookie.email@example.com';
    $orderEmailSubscriber = $this->createSubscriber($orderEmail);
    $cookieEmailSubscriber = $this->createSubscriber($cookieEmail);

    // click by order email subscriber
    $orderEmailClick = $this->createClick($this->link, $orderEmailSubscriber);

    // click by cookie email subscriber in a different newsletter
    $newsletter = $this->createNewsletter();
    $queue = $this->createQueue($newsletter, $this->subscriber);
    $link = $this->createLink($newsletter, $queue);
    $cookieEmailClick = $this->createClick($link, $cookieEmailSubscriber);

    $this->entityManager->flush();
    $_COOKIE['mailpoet_revenue_tracking'] = json_encode([
      'statistics_clicks' => $cookieEmailClick->getId(),
      'created_at' => time(),
    ]);

    $orderMock = $this->createOrderMock($orderEmail);
    $woocommercePurchases = new WooCommercePurchases(
      $this->createWooCommerceHelperMock($orderMock),
      $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class),
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->cookies,
      $this->diContainer->get(SubscriberHandler::class)
    );
    $woocommercePurchases->trackPurchase($orderMock->get_id());
    $purchaseStats = $this->statisticsWooCommercePurchasesRepository->findBy([]);
    verify(count($purchaseStats))->equals(2);
    foreach ($purchaseStats as $stats) {
      $click = $stats->getClick();
      $this->assertInstanceOf(StatisticsClickEntity::class, $click);
      $statsNewsletter = $stats->getNewsletter();
      $this->assertInstanceOf(NewsletterEntity::class, $statsNewsletter);
      if ($click->getId() === $orderEmailClick->getId()) {
        verify($statsNewsletter->getId())->equals($this->newsletter->getId());
      } else {
        verify($click->getId())->equals($cookieEmailClick->getId());
        verify($statsNewsletter->getId())->equals($newsletter->getId());
      }
    }
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }

  private function createQueue(NewsletterEntity $newsletter, SubscriberEntity $subscriber): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $this->entityManager->persist($queue);
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $sendingTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber, 1);
    $this->entityManager->persist($sendingTaskSubscriber);
    return $queue;
  }

  private function createSubscriber($email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $this->entityManager->persist($subscriber);
    $subscriber->setEmail($email);
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    return $subscriber;
  }

  private function createLink(NewsletterEntity $newsletter, SendingQueueEntity $queue): NewsletterLinkEntity {
    $link = new NewsletterLinkEntity($newsletter, $queue, 'url', 'hash');
    $this->entityManager->persist($link);
    return $link;
  }

  private function createClick(NewsletterLinkEntity $link, SubscriberEntity $subscriber, $createdDaysAgo = 5): StatisticsClickEntity {
    $newsletter = $link->getNewsletter();
    $queue = $link->getQueue();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $click = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($click);

    $timestamp = new DateTime("-$createdDaysAgo days");
    $click->setCreatedAt($timestamp);
    $click->setUpdatedAt($timestamp);
    return $click;
  }

  private function createWooCommerceHelperMock(MockObject $orderMock) {
    $mock = $this->createMock(WooCommerceHelper::class);
    $mock->method('wcGetOrder')->willReturn($orderMock);
    $mock->method('getPurchaseStates')->willReturn(['completed']);
    return $mock;
  }

  private function createOrderMock($email, $totalPrice = 15.0, $id = 123, $dateCreated = null, $status = 'completed') {
    // WC_Order class needs to be mocked without default 'disallowMockingUnknownTypes'
    // since WooCommerce may not be active (would result in error mocking undefined class)
    $mock = $this->getMockBuilder(WC_Order::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_id', 'get_date_created', 'get_billing_email', 'get_remaining_refund_amount', 'get_currency', 'get_status'])
      ->getMock();

    $mock->method('get_id')->willReturn($id);
    $mock->method('get_date_created')->willReturn($dateCreated ?? new DateTime());
    $mock->method('get_billing_email')->willReturn($email);
    $mock->method('get_remaining_refund_amount')->willReturn((string)$totalPrice);
    $mock->method('get_currency')->willReturn('EUR');
    $mock->method('get_status')->willReturn($status);
    return $mock;
  }
}
