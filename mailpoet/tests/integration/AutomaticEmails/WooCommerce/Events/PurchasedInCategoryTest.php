<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class PurchasedInCategoryTest extends \MailPoetTest {

  /** @var MockObject&WCHelper */
  private $woocommerceHelper;

  /** @var PurchasedInCategory */
  private $event;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  public function _before() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    WPFunctions::set(new WPFunctions);
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->newsletterOptionFieldsRepository = $this->diContainer->get(NewsletterOptionFieldsRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
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
    $newsletter = $this->_createNewsletter();

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

    $this->_createSubscriber($customerEmail);

    $this->event->scheduleEmail(3);
    $scheduledTask = Sending::getByNewsletterId($newsletter->getId());
    $queue = $scheduledTask->queue();
    // We only want to record the ID for the category that triggered the newsletter
    expect($queue->getMeta())->equals(['orderedProductCategories' => ['15']]);
    expect($scheduledTask)->notEmpty();
  }

  public function testItDoesNotRescheduleDueToFutureOrderWithAdditionalProduct() {
    $newsletter = $this->_createNewsletter();

    $customerEmail = 'email@example.com';
    $this->_createSubscriber($customerEmail);

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
    $queue1 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
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
    $queue2 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
    expect($queue2)->count(count($queue1));
  }

  public function testItSchedulesOnlyOnce() {
    $newsletter = $this->_createNewsletter();

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

    $this->_createSubscriber($customerEmail);

    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(3);
    $queue1 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
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
    $queue2 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
    expect($queue1)->count(count($queue2));
  }

  public function testItSchedulesAgainIfTriggerIsUpdated() {
    $newsletter = $this->_createNewsletter();

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

    $this->_createSubscriber($customerEmail);

    $this->event = new PurchasedInCategory($this->woocommerceHelper);
    $this->event->scheduleEmail(3);
    $queue1 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
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
    $queue2 = SendingQueue::where('newsletter_id', $newsletter->getId())->findMany();
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

  private function _createNewsletter(): NewsletterEntity {
    $newsletterFactory = new NewsletterFactory();
    $newsletter = $newsletterFactory
      ->withSubject('WooCommerce')
      ->withAutomaticType()
      ->withActiveStatus()
      ->create();

    $newsletterOptionFactory = new NewsletterOptionFactory();
    $newsletterOptionFactory->createMultipleOptions(
      $newsletter,
      [
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
      ]
    );

    return $newsletter;
  }

  private function _createSubscriber($customerEmail) {
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
    return $subscriber;
  }

  private function updateEmailTriggerIds(NewsletterEntity $newsletter, array $triggerIds) {
    $metaOptionField = $this->newsletterOptionFieldsRepository->findOneBy(['name' => 'meta']);
    $this->assertInstanceOf(NewsletterOptionFieldEntity::class, $metaOptionField);

    $newsletterMetaOption = $this->newsletterOptionsRepository->findOneBy(['newsletter' => $newsletter, 'optionField' => $metaOptionField]);
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
}
