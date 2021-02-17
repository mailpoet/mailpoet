<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
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
use PHPUnit\Framework\MockObject\MockObject;

class PurchasedInCategoryTest extends \MailPoetTest {

  /** @var MockObject&WCHelper */
  private $woocommerceHelper;

  /** @var PurchasedInCategory */
  private $event;

  public function _before() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    WPFunctions::set(new WPFunctions);
    WPFunctions::get()->removeAllFilters('woocommerce_payment_complete');
    $this->woocommerceHelper = $this->createMock(WCHelper::class);
    $this->event = new PurchasedInCategory($this->woocommerceHelper);
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
    $scheduledTask = Sending::getByNewsletterId($newsletter->id);
    $queue = $scheduledTask->queue();
    expect($queue->getMeta())->equals(['orderedProductCategories' => ['15', '16']]);
    expect($scheduledTask)->notEmpty();
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
    $queue1 = SendingQueue::where('newsletter_id', $newsletter->id)->findMany();
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
    $queue2 = SendingQueue::where('newsletter_id', $newsletter->id)->findMany();
    expect($queue1)->count(count($queue2));
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

  private function _createNewsletter() {
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
      ],
      $newsletter->id
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
}
