<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\ItemDetails;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs\OrderDetails;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../WooCommerceStubs/ItemDetails.php';
require_once __DIR__ . '/../WooCommerceStubs/OrderDetails.php';

/**
 * @group woo
 */
class PurchasedProductTest extends \MailPoetTest {
  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
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
    $queue1 = $this->sendingQueuesRepository->findBy(['newsletter' => $email]);

    // Create a second order with the same product and some additional product.
    // This was a cause for a duplicate email: https://mailpoet.atlassian.net/browse/MAILPOET-3254
    $event2 = $this->createOrderEvent($subscriber, [1000, 12345]);
    $event2->scheduleEmailWhenProductIsPurchased(13);
    $queue2 = $this->sendingQueuesRepository->findBy(['newsletter' => $email]);
    expect($queue2)->count(count($queue1));
  }

  public function testItCreatesASecondSendingTaskAfterTriggerUpdated() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000, 12345]);
    $this->triggerEmailForState('completed', $email, $event);
    $queues = $this->sendingQueuesRepository->findBy(['newsletter' => $email]);
    expect($queues)->count(1);

    $this->updateEmailTriggerIds($email, [1000, 12345]);

    $event2 = $this->createOrderEvent($subscriber, [1000, 12345]);
    $this->triggerEmailForState('completed', $email, $event2);
    $queues = $this->sendingQueuesRepository->findBy(['newsletter' => $email]);
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
    $sendingQueue = $this->triggerEmailForState('processing', $email, $event);
    expect($sendingQueue)->notEmpty();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItSchedulesEmailForCompletedOrder() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $sendingQueue = $this->triggerEmailForState('completed', $email, $event);
    expect($sendingQueue)->notEmpty();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItOnlySavesMetaDataForProductIdsMatchingTriggerIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000]);
    $event = $this->createOrderEvent($subscriber, [1000, 1002]);
    $sendingQueue = $this->triggerEmailForState('completed', $email, $event);
    expect($sendingQueue)->notEmpty();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  public function testItDoesNotSaveOtherTriggerIds() {
    WPFunctions::get()->removeAllFilters('woocommerce_order_status_completed');
    $subscriber = $this->createWooSubscriber();
    $email = $this->createEmailTriggeredByProductIds([1000, 2000]);
    $event = $this->createOrderEvent($subscriber, [1000]);
    $sendingQueue = $this->triggerEmailForState('completed', $email, $event);
    expect($sendingQueue)->notEmpty();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    expect($sendingQueue->getMeta())->equals(['orderedProducts' => [1000]]);
  }

  private function createEmailTriggeredByProductIds(array $triggerProductIds): NewsletterEntity {
    $newsletter = (new NewsletterFactory)
      ->withSubject('WooCommerce')
      ->withType(NewsletterEntity::TYPE_AUTOMATIC)
      ->withActiveStatus()
      ->create();
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
    (new NewsletterOptionFactory())->createMultipleOptions($newsletter, $newsletterOptionsData);

    return $newsletter;
  }

  private function createOrderEvent(SubscriberEntity $customer, array $purchasedProductIds): PurchasedProduct {
    $orderDetails = Stub::make(
      new OrderDetails(),
      [
        'get_billing_email' => $customer->getEmail(),
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
    NewsletterEntity $newsletter,
    PurchasedProduct $purchasedProductEvent
  ): ?SendingQueueEntity {
    // Order ID isn't important because we're mocking wcGetOrder in the purchase event to always return the same thing
    $orderId = 12;

    // when 'woocommerce_order_status_$state' hook is triggered an email is scheduled when appropriate
    WPFunctions::get()->doAction('woocommerce_order_status_' . $wooOrderState, $orderId);
    $purchasedProductEvent->scheduleEmailWhenProductIsPurchased($orderId);

    return $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
  }

  private function updateEmailTriggerIds(NewsletterEntity $newsletter, array $triggerIds): void {
    $newsletterMetaOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_META);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $newsletterMetaOption);
    $optionValue = json_decode((string)$newsletterMetaOption->getValue(), true);
    $this->assertIsArray($optionValue);
    $optionValue['option'] = [];
    foreach ($triggerIds as $triggerId) {
      $optionValue['option'][] = ['id' => $triggerId];
    }
    $newValue = json_encode($optionValue);
    $this->assertIsString($newValue);
    $newsletterMetaOption->setValue($newValue);
    $this->entityManager->flush();
  }

  private function createWooSubscriber(): SubscriberEntity {
    $subscriber = (new SubscriberFactory())
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

    return $subscriber;
  }

  public function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
