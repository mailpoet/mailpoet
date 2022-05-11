<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\ItemDetails;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\OrderDetails;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../WooCommerceStubs/ItemDetails.php';
require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

class PurchasedProductTest extends \MailPoetTest {
  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  public function _before() {
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
    $this->newsletterOptionFieldsRepository = $this->diContainer->get(NewsletterOptionFieldsRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
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
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);

    $metaOptionField = $this->newsletterOptionFieldsRepository->findOneBy(['name' => 'meta']);
    $this->assertInstanceOf(NewsletterOptionFieldEntity::class, $metaOptionField);

    $newsletterMetaOption = $this->newsletterOptionsRepository->findOneBy(['newsletter' => $newsletterEntity, 'optionField' => $metaOptionField]);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $newsletterMetaOption);
    $this->assertIsString($newsletterMetaOption->getValue());
    $optionValue = json_decode($newsletterMetaOption->getValue(), true);
    $this->assertIsArray($optionValue);

    $optionValue['option'] = [];
    foreach ($triggerIds as $triggerId) {
      $optionValue['option'][] = ['id' => $triggerId];
    }
    $newValue = json_encode($optionValue);
    $this->assertIsString($newValue);

    $newsletterMetaOption->setValue($newValue);
    $this->entityManager->persist($newsletterMetaOption);
    $this->entityManager->flush($newsletterMetaOption);
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
