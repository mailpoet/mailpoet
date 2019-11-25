<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\OrderDetails;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Tasks\Sending;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

class FirstPurchaseTest extends \MailPoetTest {
  function _before() {
    WPFunctions::get()->removeAllFilters('mailpoet_newsletter_shortcode');
  }

  function testItGetsEventDetails() {
    $event = new FirstPurchase();

    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(FirstPurchase::SLUG);
  }

  function testDateShortcodeHandlerReturnsShortcodeWhenItCannotDetectProperShortcode() {
    $event = new FirstPurchase();
    $shortcode = 'wrong shortcode';

    $result = $event->handleOrderDateShortcode($shortcode, true, true, true);
    expect($result)->equals($shortcode);
  }

  function testDateShortcodeHandlerReturnsShortcodeWhenQueueIsMissing() {
    $event = new FirstPurchase();
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, false);
    expect($result)->equals('success');
  }

  function testDateShortcodeHandlerReturnsCurrentDateWhenDateIsMissingInQueueMeta() {
    $event = new FirstPurchase();
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    $queue = SendingQueue::create(['task_id' => 1]);

    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, $queue);
    expect($result)->equals('success');
  }

  function testDateShortcodeHandlerReturnsSystemFormattedDate() {
    $event = new FirstPurchase();
    $shortcode = $event::ORDER_DATE_SHORTCODE;
    $queue = SendingQueue::create(['task_id' => 1]);
    WPFunctions::set(Stub::make(new WPFunctions, [
      'dateI18n' => 'success',
    ]));
    $result = $event->handleOrderDateShortcode($shortcode, true, true, $queue);
    expect($result)->equals('success');
  }

  function testOrderAmountShortcodeHandlerReturnsShortcodeWhenItCannotDetectProperShortcode() {
    $event = new FirstPurchase();
    $shortcode = 'wrong shortcode';

    $result = $event->handleOrderTotalShortcode($shortcode, true, true, true);
    expect($result)->equals($shortcode);
  }

  function testOrderAmountShortcodeHandlerReturnsFormattedZeroValueWhenQueueIsMissing() {
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

  function testOrderAmountShortcodeHandlerReturnsFormattedZeroValueWhenOrderAmountIsMissingInQueueMeta() {
    $helper = Stub::make(WCHelper::class, [
      'wcPrice' => function($price) {
        return $price;
      },
    ]);
    $event = new FirstPurchase($helper);
    $shortcode = $event::ORDER_TOTAL_SHORTCODE;
    $queue = SendingQueue::create(['task_id' => 1]);
    $result = $event->handleOrderTotalShortcode($shortcode, true, true, $queue);
    expect($result)->equals(0);
  }

  function testOrderAmountShortcodeHandlerReturnsFormattedPrice() {
    $helper = Stub::make(WCHelper::class, [
      'wcPrice' => function($price) {
        return $price;
      },
    ]);
    $event = new FirstPurchase($helper);
    $shortcode = $event::ORDER_TOTAL_SHORTCODE;
    $queue = SendingQueue::create(['task_id' => 1]);
    $queue->meta = ['order_amount' => 15];
    $result = $event->handleOrderTotalShortcode($shortcode, true, true, $queue);
    expect($result)->equals(15);
  }

  function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => false,
    ]);
    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  function testItDoesNotScheduleEmailWhenCustomerEmailIsEmpty() {
    $order_details = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => Expected::once(),
      ],
      $this
    );
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);
    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  function testItDoesNotScheduleEmailWhenItIsNotCustomersFirstPurchase() {
    $order_details = Stub::make(new OrderDetails(), ['get_billing_email' => 'test@example.com']);
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);
    $event = $this->construct(FirstPurchase::class, [$helper], [
      'getCustomerOrderCount' => 2,
    ]);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  function testItDoesNotScheduleEmailWhenCustomerIsNotAWCSegmentSubscriber() {
    $date_created = new \DateTime('2018-12-12');
    $order_details = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_date_created' => Expected::once(function() use ($date_created) {
          return $date_created;
        }),
        'get_id' => Expected::once(function() {
          return 'order_id';
        }),
      ]
    );
    $order_details->total = 'order_total';
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);

    $customer_email = 'test@example.com';
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customer_email;
    $subscriber->is_woocommerce_user = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $event = new FirstPurchase($helper);
    $result = $event->scheduleEmailWhenOrderIsPlaced(12);
    expect($result)->isEmpty();
  }

  function testItSchedulesEmailForProcessingOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    $this->_runTestItSchedulesEmailForState('processing');
  }

  function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $this->_runTestItSchedulesEmailForState('completed');
  }

  function testItSchedulesEmailOnlyOnce() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $order_id = $this->_runTestItSchedulesEmailForState('processing');
    $tasks_count_before_status_change = count(ScheduledTask::where('type', Sending::TASK_TYPE)->findMany());
    WPFunctions::get()->doAction('woocommerce_order_status_completed', $order_id);
    $tasks_count_after_status_change = count(ScheduledTask::where('type', Sending::TASK_TYPE)->findMany());
    expect($tasks_count_after_status_change)->equals($tasks_count_before_status_change);
  }

  function _runTestItSchedulesEmailForState($order_state) {
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
    $customer_email = 'test@example.com';
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customer_email;
    $subscriber->is_woocommerce_user = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $subscriber_segment = SubscriberSegment::create();
    $subscriber_segment->hydrate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => Segment::getWooCommerceSegment()->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $subscriber_segment->save();

    $date_created = new \DateTime('2018-12-12');
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => function($order_id) use ($customer_email, $date_created) {
        $order_details = Stub::construct(
          new OrderDetails(),
          [$order_id],
          [
            'get_billing_email' => $customer_email,
            'get_date_created' => $date_created,
          ]
        );
        $order_details->total = 'order_total';
        return $order_details;
      },
    ]);

    $event = new FirstPurchase($helper);
    $event->init();
    $order_id = 12;

    // ensure there are no existing scheduled tasks
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    expect($scheduled_task)->false();

    // check the customer doesn't exist yet, so he is eligible for this email
    WPFunctions::get()->doAction('woocommerce_checkout_posted_data', ['billing_email' => $customer_email]);

    // when 'woocommerce_order_status_$order_state' hook is triggered, an email should be scheduled
    WPFunctions::get()->doAction('woocommerce_order_status_' . $order_state, $order_id);
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    $meta = $scheduled_task->queue()->getMeta();
    expect($meta)->equals(
      [
        'order_amount' => 'order_total',
        'order_date' => $date_created->getTimestamp(),
        'order_id' => $order_id,
      ]
    );
    return $order_id;
  }

  function _createNewsletterOption(array $options, $newsletter_id) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)
        ->where('newsletter_type', Newsletter::TYPE_AUTOMATIC)
        ->findOne();
      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->hydrate(
          [
            'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
            'name' => $option,
          ]
        );
        $newsletter_option_field->save();
      }

      $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter_id)
        ->where('option_field_id', $newsletter_option_field->id)
        ->findOne();
      if (!$newsletter_option) {
        $newsletter_option = NewsletterOption::create();
        $newsletter_option->hydrate(
          [
            'newsletter_id' => $newsletter_id,
            'option_field_id' => $newsletter_option_field->id,
            'value' => $value,
          ]
        );
        $newsletter_option->save();
      }
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    WPFunctions::set(new WPFunctions);
  }
}
