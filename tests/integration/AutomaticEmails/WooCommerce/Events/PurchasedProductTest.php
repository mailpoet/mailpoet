<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\ItemDetails;
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

require_once __DIR__ . '/../WooCommerceStubs/ItemDetails.php';
require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

class PurchasedProductTest extends \MailPoetTest {
  function _before() {
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
  }

  function testItGetsEventDetails() {
    $event = new PurchasedProduct();
    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(PurchasedProduct::SLUG);
  }

  function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => false,
    ]);
    $event = new PurchasedProduct($helper);
    $result = $event->scheduleEmailWhenProductIsPurchased(12);
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
    $event = new PurchasedProduct($helper);

    $result = $event->scheduleEmailWhenProductIsPurchased(12);
    expect($result)->isEmpty();
  }

  function testItDoesNotScheduleEmailWhenCustomerIsNotAWCSegmentSubscriber() {
    $order_details = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_items' => function() {
          return [
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => 12,
              ]
            ),
          ];
        },
      ]
    );
    $order_details->total = 'order_total';
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);
    $event = new PurchasedProduct($helper);

    $result = $event->scheduleEmailWhenProductIsPurchased(12);
    expect($result)->isEmpty();
  }


  function testItDoesNotScheduleEmailWhenPurchasedProductDoesNotMatchConfiguredProductIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $product_id = 1000;
    $incorrect_product_ids = [
      2000,
      3000,
    ];
    $this->_createNewsletterOption(
      [
        'group' => WooCommerce::SLUG,
        'event' => PurchasedProduct::SLUG,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
        'meta' => json_encode(
          [
            'option' => [
              ['id' => $product_id],
            ],
          ]),
      ],
      $newsletter->id
    );
    $customer_email = 'test@example.com';
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customer_email;
    $subscriber->is_woocommerce_user = 1;
    $subscriber->save();

    $order_details = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_items' => function() use ($incorrect_product_ids) {
          return [
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $incorrect_product_ids[0],
              ]
            ),
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $incorrect_product_ids[1],
              ]
            ),
          ];
        },
      ]
    );
    $order_details->total = 'order_total';
    $order_id = 12;
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);
    $event = new PurchasedProduct($helper);

    WPFunctions::get()->doAction('woocommerce_order_status_completed', $order_id);
    $event->scheduleEmailWhenProductIsPurchased($order_id);
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    expect($scheduled_task)->isEmpty();
  }

  function testItSchedulesEmailForProcessingOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    $this->_runTestItSchedulesEmailForState('processing');
  }

  function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $this->_runTestItSchedulesEmailForState('completed');
  }


  function _runTestItSchedulesEmailForState($state) {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $product_id = 1000;
    $incorrect_product_id = 2000;
    $this->_createNewsletterOption(
      [
        'group' => WooCommerce::SLUG,
        'event' => PurchasedProduct::SLUG,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
        'meta' => json_encode(
          [
            'option' => [
              ['id' => $product_id],
            ],
          ]),
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

    $order_details = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_items' => function() use ($incorrect_product_id, $product_id) {
          return [
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $incorrect_product_id,
              ]
            ),
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $product_id,
              ]
            ),
          ];
        },
      ]
    );
    $order_details->total = 'order_total';
    $order_id = 12;
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $order_details,
    ]);

    $event = new PurchasedProduct($helper);

    // ensure there are no existing scheduled tasks
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    expect($scheduled_task)->false();

    // when 'woocommerce_order_status_$state' hook is triggered, an email should be scheduled
    WPFunctions::get()->doAction('woocommerce_order_status_' . $state, $order_id);
    $event->scheduleEmailWhenProductIsPurchased($order_id);
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    expect($scheduled_task)->notEmpty();
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
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    WPFunctions::set(new WPFunctions);
  }
}
