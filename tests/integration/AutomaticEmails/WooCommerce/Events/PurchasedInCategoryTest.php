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
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class PurchasedInCategoryTest extends \MailPoetTest {

  /** @var MockObject */
  private $woocommerce_helper;

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
    $this->woocommerce_helper = $this->makeEmpty(WCHelper::class, []);
    $this->event = new PurchasedInCategory($this->woocommerce_helper);
  }

  public function testItGetsEventDetails() {
    $result = $this->event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(PurchasedInCategory::SLUG);
  }

  public function testItDoesNotScheduleEmailWhenOrderDetailsAreNotAvailable() {
    $this->woocommerce_helper
      ->expects($this->once())
      ->method('wcGetOrder')
      ->will($this->returnValue(false));
    $this->event->scheduleEmail(1);
  }

  public function testItDoesNotScheduleEmailWhenNoSubscriber() {
    $order = $this->getOrderMock();
    $this->woocommerce_helper
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

    $customer_email = 'email@example.com';
    $order = $this->getOrderMock(['15', '16']);
    $this->woocommerce_helper
      ->expects($this->once())
      ->method('wcGetOrder')
      ->will($this->returnValue($order));
    $order
      ->expects($this->atLeastOnce())
      ->method('get_billing_email')
      ->will($this->returnValue($customer_email));

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

    $this->event->scheduleEmail(3);
    $scheduled_task = Sending::getByNewsletterId($newsletter->id);
    expect($scheduled_task)->notEmpty();
  }

  private function getOrderMock($categories = ['123']) {
    $product_mock = $this->getMockBuilder(\WC_Product::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_category_ids'])
      ->getMock();

    $product_mock->method('get_category_ids')->willReturn($categories);

    $order_item_product_mock = $this->getMockBuilder(\WC_Order_Item_Product::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_product'])
      ->getMock();

    $order_item_product_mock->method('get_product')->willReturn($product_mock);

    $order_mock = $this->getMockBuilder(\WC_Order::class)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods(['get_billing_email', 'get_items'])
      ->getMock();

    $order_mock->method('get_items')->willReturn([$order_item_product_mock]);

    return $order_mock;
  }

  public function _createNewsletterOption(array $options, $newsletter_id) {
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
}
