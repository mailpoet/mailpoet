<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\OrderDetails;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WooCommerce\Helper;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

/**
 * @group woo
 */
class FirstPurchaseTest extends \MailPoetTest {
  /** @var NewsletterFactory */
  private $newsletterFactory;

  /** @var NewsletterOptionFactory */
  private $newsletterOptionFactory;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueueRepository;

  /** @var Helper */
  private $wooCommerceHelper;

  public function _before() {
    $this->newsletterFactory = new NewsletterFactory();
    $this->newsletterOptionFactory = new NewsletterOptionFactory();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->sendingQueueRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->wooCommerceHelper = $this->diContainer->get(Helper::class);
    WPFunctions::get()->removeAllFilters('mailpoet_newsletter_shortcode');
  }

  public function testItGetsEventDetails() {
    $event = new FirstPurchase();

    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(FirstPurchase::SLUG);
  }

  public function testDateShortcodeHandlerReturnsShortcodeWhenItCannotDetectProperShortcode() {
    $event = new FirstPurchase();
    $shortcode = 'wrong shortcode';

    $result = $event->handleOrderDateShortcode($shortcode, true, true, true);
    expect($result)->equals($shortcode);
  }

  public function testDateShortcodeHandlerReturnsShortcodeWhenQueueIsMissing() {
    $event = new FirstPurchase();
    $shortcode = FirstPurchase::ORDER_DATE_SHORTCODE;
    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, false);
    expect($result)->equals('success');
  }

  public function testDateShortcodeHandlerReturnsCurrentDateWhenDateIsMissingInQueueMeta() {
    $event = new FirstPurchase();
    $shortcode = FirstPurchase::ORDER_DATE_SHORTCODE;
    $queue = $this->createSendingQueue($this->newsletterFactory->create());

    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, $queue);
    expect($result)->equals('success');
  }

  public function testDateShortcodeHandlerReturnsSystemFormattedDate() {
    $event = new FirstPurchase();
    $shortcode = FirstPurchase::ORDER_DATE_SHORTCODE;
    $queue = $this->createSendingQueue($this->newsletterFactory->create());
    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, $queue);
    expect($result)->equals('success');
  }

  public function testOrderAmountShortcodeHandlerReturnsShortcodeWhenItCannotDetectProperShortcode() {
    $event = new FirstPurchase();
    $shortcode = 'wrong shortcode';

    $result = $event->handleOrderTotalShortcode($shortcode, true, true, true);
    expect($result)->equals($shortcode);
  }

  public function testOrderAmountShortcodeHandlerReturnsFormattedZeroValueWhenQueueIsMissing() {
    $helper = Stub::make(WCHelper::class, [
      'wcPrice' => function($price) {
        return $price;
      },
    ]);
    $event = new FirstPurchase($helper);
    $shortcode = FirstPurchase::ORDER_TOTAL_SHORTCODE;
    $result = $event->handleOrderTotalShortcode($shortcode, true, true, false);
    expect($result)->equals(0);
  }

  public function testOrderAmountShortcodeHandlerReturnsFormattedZeroValueWhenOrderAmountIsMissingInQueueMeta() {
    $helper = Stub::make(WCHelper::class, [
      'wcPrice' => function($price) {
        return $price;
      },
    ]);
    $event = new FirstPurchase($helper);
    $shortcode = FirstPurchase::ORDER_TOTAL_SHORTCODE;
    $queue = $this->createSendingQueue($this->newsletterFactory->create());
    $result = $event->handleOrderTotalShortcode($shortcode, true, true, $queue);
    expect($result)->equals(0);
  }

  public function testOrderAmountShortcodeHandlerReturnsFormattedPrice() {
    $helper = Stub::make(WCHelper::class, [
      'wcPrice' => function($price) {
        return $price;
      },
    ]);
    $event = new FirstPurchase($helper);
    $shortcode = FirstPurchase::ORDER_TOTAL_SHORTCODE;
    $queue = $this->createSendingQueue($this->newsletterFactory->create(), ['order_amount' => 15]);
    $result = $event->handleOrderTotalShortcode($shortcode, true, true, $queue);
    expect($result)->equals(15);
  }

  public function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => false,
    ]);
    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  public function testItDoesNotScheduleEmailWhenCustomerEmailIsEmpty() {
    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => Expected::once(),
      ],
      $this
    );
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);
    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  public function testItDoesNotScheduleEmailWhenItIsNotCustomersFirstPurchase() {
    $orderDetails = Stub::make(new OrderDetails(), ['get_billing_email' => 'test@example.com']);
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);
    $event = $this->construct(FirstPurchase::class, [$helper], [
      'getCustomerOrderCount' => 2,
    ]);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  public function testItDoesNotScheduleEmailWhenCustomerIsNotAWCSegmentSubscriber() {
    $dateCreated = new \DateTime('2018-12-12');
    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_date_created' => Expected::once(function() use ($dateCreated) {
          return $dateCreated;
        }),
        'get_id' => Expected::once(function() {
          return 'order_id';
        }),
      ]
    );
    $orderDetails->total = 'order_total';
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
      'wcGetCustomerOrderCount' => 0,
    ]);

    $customerEmail = 'test@example.com';
    $subscriber = (new SubscriberFactory())->withEmail($customerEmail)
      ->withIsWooCommerceUser()
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  public function testItScheduleEmailForGuestCustomer() {
    $newsletter = $this->createWooCommerceEmail();
    $customerEmail = 'guest_customer@example.com';
    $subscriber = (new SubscriberFactory())->withEmail($customerEmail)
      ->withIsWooCommerceUser()
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subscriberSegment = new SubscriberSegmentEntity(
      $this->segmentsRepository->getWooCommerceSegment(),
      $subscriber,
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    $this->tester->createWooCommerceOrder();
    $order = $this->tester->createWooCommerceOrder(['billing_email' => $customerEmail]);
    $orderDate = $order->get_date_created();
    $this->assertInstanceOf(\WC_DateTime::class, $orderDate);

    $event = new FirstPurchase($this->wooCommerceHelper);
    $result = $event->scheduleEmailWhenOrderIsPlaced($order->get_id());
    expect($result)->isEmpty();

    $sendingQueue = $this->sendingQueueRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $meta = $sendingQueue->getMeta();
    expect($meta)->equals([
      'order_amount' => $order->get_total(),
      'order_date' => $orderDate->getTimestamp(),
      'order_id' => $order->get_id(),
    ]);
  }

  public function testItSchedulesEmailForProcessingOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    $this->_runTestItSchedulesEmailForState('processing');
  }

  public function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $this->_runTestItSchedulesEmailForState('completed');
  }

  public function testItSchedulesEmailOnlyOnce() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $orderId = $this->_runTestItSchedulesEmailForState('processing');
    $tasksCountBeforeStatusChange = count($this->scheduledTasksRepository->findBy(['type' => Sending::TASK_TYPE]));
    WPFunctions::get()->doAction('woocommerce_order_status_completed', $orderId);
    $tasksCountAfterStatusChange = count($this->scheduledTasksRepository->findBy(['type' => Sending::TASK_TYPE]));
    expect($tasksCountAfterStatusChange)->equals($tasksCountBeforeStatusChange);
  }

  public function _runTestItSchedulesEmailForState($orderState) {
    $newsletter = $this->createWooCommerceEmail();
    $customerEmail = 'test@example.com';
    $subscriber = (new SubscriberFactory())->withEmail($customerEmail)
      ->withIsWooCommerceUser()
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $subscriberSegment = new SubscriberSegmentEntity(
      $this->segmentsRepository->getWooCommerceSegment(),
      $subscriber,
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();

    $dateCreated = new \DateTime('2018-12-12');
    $helper = Stub::make(WCHelper::class, [
      'wcGetCustomerOrderCount' => 0,
      'wcGetOrder' => function($orderId) use ($customerEmail, $dateCreated) {
        $orderDetails = Stub::construct(
          new OrderDetails(),
          [$orderId],
          [
            'get_billing_email' => $customerEmail,
            'get_date_created' => $dateCreated,
          ]
        );
        $orderDetails->total = 'order_total';
        return $orderDetails;
      },
    ]);

    $event = new FirstPurchase($helper);
    $event->init();
    $orderId = 12;

    // ensure there are no existing scheduled tasks
    $scheduledTask = $this->sendingQueueRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertNull($scheduledTask);

    // check the customer doesn't exist yet, so he is eligible for this email
    WPFunctions::get()->doAction('woocommerce_checkout_posted_data', ['billing_email' => $customerEmail]);

    // when 'woocommerce_order_status_$order_state' hook is triggered, an email should be scheduled
    WPFunctions::get()->doAction('woocommerce_order_status_' . $orderState, $orderId);
    $sendingQueue = $this->sendingQueueRepository->findOneBy(['newsletter' => $newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $meta = $sendingQueue->getMeta();
    expect($meta)->equals([
      'order_amount' => 'order_total',
      'order_date' => $dateCreated->getTimestamp(),
      'order_id' => $orderId,
    ]);
    return $orderId;
  }

  private function createWooCommerceEmail(): NewsletterEntity {
    $newsletter = $this->newsletterFactory
      ->withSubject('WooCommerce')
      ->withType(NewsletterEntity::TYPE_AUTOMATIC)
      ->withActiveStatus()
      ->create();
    $this->newsletterOptionFactory->createMultipleOptions($newsletter, [
      'group' => WooCommerce::SLUG,
      'event' => FirstPurchase::SLUG,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
      'sendTo' => 'user',
    ]);

    return $newsletter;
  }

  private function createSendingQueue(NewsletterEntity $newsletter, array $meta = []): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $this->entityManager->flush();

    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setNewsletter($newsletter);
    $sendingQueue->setMeta($meta);
    $sendingQueue->setTask($task);
    $this->entityManager->persist($sendingQueue);
    $this->entityManager->flush();
    return $sendingQueue;
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
