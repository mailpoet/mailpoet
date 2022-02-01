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

  public function testItDoesNotScheduleEmailWhenAlreadySent() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $event->scheduleEmailWhenProductIsPurchased(12);
    $queue1 = SendingQueue::where('newsletter_id', $email->id)->findMany();

    // Create a second order with the same product and some additional product.
    // This was a cause for a duplicate email: https://mailpoet.atlassian.net/browse/MAILPOET-3254
    $event2 = $this->createOrderEvent($subscriber, [1000, 12345]);
    $event2->scheduleEmailWhenProductIsPurchased(13);
    $queue2 = SendingQueue::where('newsletter_id', $email->id)->findMany();
    expect($queue2)->count(count($queue1));
  }

  public function testItDoesntCreateASecondSendingTaskWhenAlreadySentWhat() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $this->triggerEmailForState('completed', $email, $event);
    $queues = SendingQueue::where('newsletter_id', $email->id)->findMany();
    expect($queues)->count(1);

    // Create a second order with the same product and some additional product.
    // This was a cause for a duplicate email: https://mailpoet.atlassian.net/browse/MAILPOET-3254
    $event2 = $this->createOrderEvent($subscriber, [1000, 12345]);
    $sendingTask2 = $this->triggerEmailForState('completed', $email, $event2);
    $queues = SendingQueue::where('newsletter_id', $email->id)->findMany();
    expect($queues)->count(1);
  }

  public function testItCreatesASecondSendingTaskAfterTriggerUpdated() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000, 12345]);
    $this->triggerEmailForState('completed', $email, $event);
    $queues = SendingQueue::where('newsletter_id', $email->id)->findMany();
    expect($queues)->count(1);

    $this->updateEmailTriggerIds($email, [1000, 12345]);

    $event2 = $this->createOrderEvent($subscriber, [1000, 12345]);
    $this->triggerEmailForState('completed', $email, $event2);
    $queues = SendingQueue::where('newsletter_id', $email->id)->findMany();
    expect($queues)->count(2);
  }

  public function testItDoesNotScheduleEmailWhenPurchasedProductDoesNotMatchConfiguredProductIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([2000, 3000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $scheduledTask = $this->triggerEmailForState('completed', $email, $event);
    expect($scheduledTask)->isEmpty();
  }

  public function testItSchedulesEmailForProcessingOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_processing');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $scheduledTask = $this->triggerEmailForState('processing', $email, $event);
    expect($scheduledTask)->notEmpty();
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $scheduledTask = $this->triggerEmailForState('completed', $email, $event);
    expect($scheduledTask)->notEmpty();
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItOnlySavesMetaDataForProductIdsMatchingTriggerIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000, 1002]);
    $scheduledTask = $this->triggerEmailForState('completed', $email, $event);
    expect($scheduledTask)->notEmpty();
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItDoesNotSaveOtherTriggerIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000, 2000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $scheduledTask = $this->triggerEmailForState('completed', $email, $event);
    expect($scheduledTask)->notEmpty();
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  private function createEmailTriggeredByProductIds(array $triggerProductIds): Newsletter {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'preheader',
        'type' => Newsletter::TYPE_AUTOMATIC,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $newsletterOptionsData = [
      'sendTo' => 'user', // "Already sent" test fails without this
      'group' => WooCommerce::SLUG,
      'event' => PurchasedProduct::SLUG,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    $meta = ['option' => []];
    foreach ($triggerProductIds as $id) {
      $meta['option'][] = ['id' => $id];
    }
    $newsletterOptionsData['meta'] = json_encode($meta);
    $this->_createNewsletterOption(
      $newsletterOptionsData,
      $newsletter->id
    );

    return $newsletter;
  }

  private function createOrderEvent(Subscriber $customer, array $purchasedProductIds) {
    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => $customer->get('email'),
        'get_items' => function() use ($purchasedProductIds) {
          return array_map(function($id) {
            return Stub::make(
              \WC_Order_Item_Product::class,
              [
                'get_product_id' => $id,
              ]
            );
          }, $purchasedProductIds);
        },
      ]
    );
    $orderDetails->total = 'order_total';
    $helper = Stub::make(WCHelper::class, [
      'wcGetOrder' => $orderDetails,
    ]);

    return new PurchasedProduct($helper);
  }

  private function triggerEmailForState(
    string $wooOrderState,
    Newsletter $newsletter,
    PurchasedProduct $purchasedProductEvent
  ) {
    // Order ID isn't important because we're mocking wcGetOrder in the purchase event to always return the same thing
    $orderId = 12;

    // when 'woocommerce_order_status_$state' hook is triggered an email is scheduled when appropriate
    WPFunctions::get()->doAction('woocommerce_order_status_' . $wooOrderState, $orderId);
    $purchasedProductEvent->scheduleEmailWhenProductIsPurchased($orderId);

    return Sending::getByNewsletterId($newsletter->id);
  }

  private function updateEmailTriggerIds(Newsletter $newsletter, array $triggerIds) {
    $metaOptionField = NewsletterOptionField::where('name', 'meta')->findOne();
    $this->assertInstanceOf(NewsletterOptionField::class, $metaOptionField);
    $newsletterMetaOption = NewsletterOption::where(['newsletter_id' => $newsletter->id, 'option_field_id' => $metaOptionField->id])->findOne();
    $this->assertInstanceOf(NewsletterOption::class, $newsletterMetaOption);
    $optionValue = json_decode($newsletterMetaOption->value, true);
    $this->assertIsArray($optionValue);
    $optionValue['option'] = [];
    foreach ($triggerIds as $triggerId) {
      $optionValue['option'][] = ['id' => $triggerId];
    }
    $newValue = json_encode($optionValue);
    $this->assertIsString($newValue);
    $newsletterMetaOption->set('value', $newValue);
    $newsletterMetaOption->save();
  }

  private function _createNewsletterOption(array $options, $newsletterId) {
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

  private function createWooSubscriber(array $data = []) {
    $data = array_merge(Fixtures::get('subscriber_template'), $data);
    $subscriber = Subscriber::createOrUpdate($data);
    $subscriber->isWoocommerceUser = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;

    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->hydrate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => Segment::getWooCommerceSegment()->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $subscriberSegment->save();

    return $subscriber->save();
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
