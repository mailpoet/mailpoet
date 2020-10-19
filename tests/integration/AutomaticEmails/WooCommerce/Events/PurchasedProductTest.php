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
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/../WooCommerceStubs/ItemDetails.php';
require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

class PurchasedProductTest extends \MailPoetTest {
  public function _before() {
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
  }

  public function testItGetsEventDetails() {
    $event = new PurchasedProduct();
    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(PurchasedProduct::SLUG);
  }

  public function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => false,
    ]);
    $event = new PurchasedProduct($helper);
    $result = $event->scheduleEmailWhenProductIsPurchased(12);
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
    $event = new PurchasedProduct($helper);

    $result = $event->scheduleEmailWhenProductIsPurchased(12);
    expect($result)->isEmpty();
  }

  public function testItDoesNotScheduleEmailWhenCustomerIsNotAWCSegmentSubscriber() {
    $orderDetails = Stub::make(
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
    $orderDetails->total = 'order_total';
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);
    $event = new PurchasedProduct($helper);

    $result = $event->scheduleEmailWhenProductIsPurchased(12);
    expect($result)->isEmpty();
  }

  public function testItDoesNotScheduleEmailWhenPurchasedProductDoesNotMatchConfiguredProductIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $productId = 1000;
    $incorrectProductIds = [
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
              ['id' => $productId],
            ],
          ]),
      ],
      $newsletter->id
    );
    $customerEmail = 'test@example.com';
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customerEmail;
    $subscriber->isWoocommerceUser = 1;
    $subscriber->save();

    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_items' => function() use ($incorrectProductIds) {
          return [
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $incorrectProductIds[0],
              ]
            ),
            Stub::make(
              new ItemDetails(),
              [
                'get_product_id' => $incorrectProductIds[1],
              ]
            ),
          ];
        },
      ]
    );
    $orderDetails->total = 'order_total';
    $orderId = 12;
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);
    $event = new PurchasedProduct($helper);

    WPFunctions::get()->doAction('woocommerce_order_status_completed', $orderId);
    $event->scheduleEmailWhenProductIsPurchased($orderId);
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    expect($scheduledTask)->isEmpty();
  }

  public function testItSchedulesEmailForProcessingOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    $this->_runTestItSchedulesEmailForState('processing');
  }

  public function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $this->_runTestItSchedulesEmailForState('completed');
  }

  public function _runTestItSchedulesEmailForState($state) {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $productId = 1000;
    $incorrectProductId = 2000;
    $this->_createNewsletterOption(
      [
        'group' => WooCommerce::SLUG,
        'event' => PurchasedProduct::SLUG,
        'afterTimeType' => 'days',
        'afterTimeNumber' => 1,
        'meta' => json_encode(
          [
            'option' => [
              ['id' => $productId],
            ],
          ]),
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

    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => 'test@example.com',
        'get_items' => function() use ($incorrectProductId, $productId) {
          return [
            Stub::make(
              \WC_Order_Item_Product::class,
              [
                'get_product_id' => $incorrectProductId,
              ]
            ),
            Stub::make(
              \WC_Order_Item_Product::class,
              [
                'get_product_id' => $productId,
              ]
            ),
          ];
        },
      ]
    );
    $orderDetails->total = 'order_total';
    $orderId = 12;
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);

    $event = new PurchasedProduct($helper);

    // ensure there are no existing scheduled tasks
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    expect($scheduledTask)->false();

    // when 'woocommerce_order_status_$state' hook is triggered, an email should be scheduled
    WPFunctions::get()->doAction('woocommerce_order_status_' . $state, $orderId);
    $event->scheduleEmailWhenProductIsPurchased($orderId);
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    expect($scheduledTask)->notEmpty();
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProducts' => [$incorrectProductId, $productId]]);
    return $orderId;
  }

  public function _createNewsletterOption(array $options, $newsletterId) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)
        ->where('newsletter_type', Newsletter::TYPE_AUTOMATIC)
        ->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->hydrate(
          [
            'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
            'name' => $option,
          ]
        );
        $newsletterOptionField->save();
      }

      $newsletterOption = NewsletterOption::where('newsletter_id', $newsletterId)
        ->where('option_field_id', $newsletterOptionField->id)
        ->findOne();
      if (!$newsletterOption) {
        $newsletterOption = NewsletterOption::create();
        $newsletterOption->hydrate(
          [
            'newsletter_id' => $newsletterId,
            'option_field_id' => $newsletterOptionField->id,
            'value' => $value,
          ]
        );
        $newsletterOption->save();
      }
    }
  }

  public function _after() {
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
