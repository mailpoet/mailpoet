<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\OrderDetails;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

class FirstPurchaseTest extends \MailPoetTest {
  public function _before() {
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
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, false);
    expect($result)->equals('success');
  }

  public function testDateShortcodeHandlerReturnsCurrentDateWhenDateIsMissingInQueueMeta() {
    $event = new FirstPurchase();
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    $queue = SendingQueue::create();

    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, $queue);
    expect($result)->equals('success');
  }

  public function testDateShortcodeHandlerReturnsSystemFormattedDate() {
    $event = new FirstPurchase();
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    $queue = SendingQueue::create();
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
    $shortcode = $event::ORDER_TOTAL_SHORTCODE;
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
    $shortcode = $event::ORDER_TOTAL_SHORTCODE;
    $queue = SendingQueue::create();
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
    $shortcode = $event::ORDER_TOTAL_SHORTCODE;
    $queue = SendingQueue::create();
    $queue->meta = ['order_amount' => 15];
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
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customerEmail;
    $subscriber->isWoocommerceUser = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
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
    $tasksCountBeforeStatusChange = count(ScheduledTask::where('type', Sending::TASK_TYPE)->findMany());
    WPFunctions::get()->doAction('woocommerce_order_status_completed', $orderId);
    $tasksCountAfterStatusChange = count(ScheduledTask::where('type', Sending::TASK_TYPE)->findMany());
    expect($tasksCountAfterStatusChange)->equals($tasksCountBeforeStatusChange);
  }

  public function _runTestItSchedulesEmailForState($orderState) {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $this->_createNewsletterOption(
      [
        'group' => WooCommerce::SLUG,
        'event' => FirstPurchase::SLUG,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
        'sendTo' => 'user',
      ],
      $newsletter->id
    );
    $customerEmail = 'test@example.com';
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customerEmail;
    $subscriber->isWoocommerceUser = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->hydrate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => Segment::getWooCommerceSegment()->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $subscriberSegment->save();

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
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    expect($scheduledTask)->false();

    // check the customer doesn't exist yet, so he is eligible for this email
    WPFunctions::get()->doAction('woocommerce_checkout_posted_data', ['billing_email' => $customerEmail]);

    // when 'woocommerce_order_status_$order_state' hook is triggered, an email should be scheduled
    WPFunctions::get()->doAction('woocommerce_order_status_' . $orderState, $orderId);
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    $meta = $scheduledTask->queue()->getMeta();
    expect($meta)->equals(
      [
        'order_amount' => 'order_total',
        'order_date' => $dateCreated->getTimestamp(),
        'order_id' => $orderId,
      ]
    );
    return $orderId;
  }

  public function _createNewsletterOption(array $options, $newsletterId) {
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletterId);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterOptionFactory = new NewsletterOptionFactory();
    $newsletterOptionFactory->createMultipleOptions(
      $newsletterEntity,
      $options
    );
  }

  public function _after() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    WPFunctions::set(new WPFunctions);
  }
}
