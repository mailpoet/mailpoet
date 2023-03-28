<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group woo
 */
class PurchasedInCategoryTest extends \MailPoetTest {

  /** @var MockObject&WCHelper */
  private $woocommerceHelper;

  /** @var PurchasedInCategory */
  private $event;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    WPFunctions::set(new WPFunctions);
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItGetsEventDetails() {
    $result = $this->event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(PurchasedInCategory::SLUG);
  }

  public function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $this->woocommerceHelper
      ->expects($this->once())
      ->method('wcGetOrder')
      ->will($this->returnValue(false));
    $this->event->scheduleEmail(1);
  }

  public function testItDoesNotScheduleEmailWhenNoSubscriber() {
    $order = $this->getOrderMock();
    $this->woocommerceHelper
      ->expects($this->once())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->atLeastOnce())
      ->method('get_billing_email')
      ->will($this->returnValue('email@example.com'));
    $this->event->scheduleEmail(2);
  }

  public function testItSchedules() {
    $newsletter = $this->createNewsletter();

    $customerEmail = 'email@example.com';
    $order = $this->getOrderMock(['15', '16']);
    $this->woocommerceHelper
      ->expects($this->once())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->atLeastOnce())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));

    $this->createSubscriber($customerEmail);

    $this->event->scheduleEmail(3);
    $queue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $newsletter]);
    // We only want to record the ID for the category that triggered the newsletter
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    expect($queue->getMeta())->equals(['orderedProductCategories' => ['15']]);
    expect($queue->getTask())->notEmpty();
  }

  public function testItDoesNotRescheduleDueToFutureOrderWithAdditionalProduct() {
    $newsletter = $this->createNewsletter();

    $customerEmail = 'email@example.com';
    $this->createSubscriber($customerEmail);

    $order = $this->getOrderMock(['15']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));

    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(3);
    $queue1 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    expect($queue1)->notEmpty();

    $order = $this->getOrderMock(['15', '17']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(4);
    $queue2 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    $this->assertCount(count($queue1), $queue2);
  }

  public function testItSchedulesOnlyOnce() {
    $newsletter = $this->createNewsletter();

    $customerEmail = 'email@example.com';
    $order = $this->getOrderMock(['15', '16']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));

    $this->createSubscriber($customerEmail);

    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(3);
    $queue1 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    expect($queue1)->notEmpty();

    $order = $this->getOrderMock(['15']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(4);
    $queue2 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    $this->assertCount(count($queue1), $queue2);
  }

  public function testItSchedulesAgainIfTriggerIsUpdated() {
    $newsletter = $this->createNewsletter();

    $customerEmail = 'email@example.com';
    $order = $this->getOrderMock(['15', '16']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));

    $this->createSubscriber($customerEmail);

    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(3);
    $queue1 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    expect($queue1)->count(1);

    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->updateEmailTriggerIds($newsletter, ['16']);
    $order = $this->getOrderMock(['16']);
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->woocommerceHelper
      ->expects($this->any())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->any())
      ->method('get_billing_email')
      ->will($this->returnValue($customerEmail));
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(4);
    $queue2 = $this->sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    expect($queue2)->count(2);
  }

  private function getOrderMock($categories = ['123']) {
    $productMock = $this->getMockBuilder(\WC_Product::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_category_ids', 'get_type'])
      ->getMock();

    $productMock->method('get_category_ids')->willReturn($categories);

    $orderItemProductMock = $this->getMockBuilder(\WC_Order_Item_Product::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_product'])
      ->getMock();

    $orderItemProductMock->method('get_product')->willReturn($productMock);

    $orderMock = $this->getMockBuilder(\WC_Order::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_billing_email', 'get_items'])
      ->getMock();

    $orderMock->method('get_items')->willReturn([$orderItemProductMock]);

    return $orderMock;
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = (new NewsletterFactory())
      ->withSubject('WooCommerce')
      ->withType(NewsletterEntity::TYPE_AUTOMATIC)
      ->withActiveStatus()
      ->create();
    (new NewsletterOptionFactory())->createMultipleOptions($newsletter, [
      'sendTo' => 'user',
      'group' => WooCommerce::SLUG,
      'event' => PurchasedInCategory::SLUG,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
      'meta' => json_encode(
        [
          'option' => [
            ['id' => '15'],
          ],
        ]),
    ]);
    return $newsletter;
  }

  private function createSubscriber($customerEmail): SubscriberEntity {
    $subscriber = (new SubscriberFactory())
      ->withEmail($customerEmail)
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

  private function updateEmailTriggerIds(NewsletterEntity $newsletter, array $triggerIds) {
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
}
