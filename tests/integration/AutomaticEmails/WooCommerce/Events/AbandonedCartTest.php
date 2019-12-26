<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Cart;
use WooCommerce;
use WP_User;

class AbandonedCartTest extends \MailPoetTest {
  const SCHEDULE_EMAIL_AFTER_HOURS = 5;

  /** @var Carbon */
  private $current_time;

  /** @var WPFunctions|MockObject */
  private $wp;

  /** @var WooCommerce|MockObject */
  private $woo_commerce_mock;

  /** @var WC_Cart|MockObject */
  private $woo_commerce_cart_mock;

  /** @var WooCommerceHelper|MockObject */
  private $woo_commerce_helper_mock;

  /** @var AbandonedCartPageVisitTracker|MockObject */
  private $page_visit_tracker_mock;

  public function _before() {
    $this->cleanup();

    $this->current_time = Carbon::createFromTimestamp((new WPFunctions())->currentTime('timestamp'));
    Carbon::setTestNow($this->current_time);

    $this->wp = $this->makeEmpty(WPFunctions::class, [
      'currentTime' => $this->current_time->getTimestamp(),
    ]);
    WPFunctions::set($this->wp);

    $this->woo_commerce_mock = $this->mockWooCommerceClass(WooCommerce::class, []);
    $this->woo_commerce_cart_mock = $this->mockWooCommerceClass(WC_Cart::class, ['is_empty']);
    $this->woo_commerce_mock->cart = $this->woo_commerce_cart_mock;
    $this->woo_commerce_helper_mock = $this->make(WooCommerceHelper::class, [
      'isWooCommerceActive' => true,
      'WC' => $this->woo_commerce_mock,
    ]);

    $this->page_visit_tracker_mock = $this->makeEmpty(AbandonedCartPageVisitTracker::class);
  }

  public function testItGetsEventDetails() {
    $event = new AbandonedCart();
    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(AbandonedCart::SLUG);
  }

  public function testItRegistersWooCommerceCartEvents() {
    $abandoned_cart_email = $this->createAbandonedCartEmail();

    $registered_actions = [];
    $this->wp->method('addAction')->willReturnCallback(function ($name) use (&$registered_actions) {
      $registered_actions[] = $name;
    });
    $abandoned_cart_email->init();

    expect($registered_actions)->contains('woocommerce_add_to_cart');
    expect($registered_actions)->contains('woocommerce_cart_item_removed');
    expect($registered_actions)->contains('woocommerce_after_cart_item_quantity_update');
    expect($registered_actions)->contains('woocommerce_before_cart_item_quantity_zero');
    expect($registered_actions)->contains('woocommerce_cart_emptied');
    expect($registered_actions)->contains('woocommerce_cart_item_restored');
  }

  public function testItRegistersPageVisitEvent() {
    $abandoned_cart_email = $this->createAbandonedCartEmail();

    $registered_actions = [];
    $this->wp->method('addAction')->willReturnCallback(function ($name) use (&$registered_actions) {
      $registered_actions[] = $name;
    });
    $abandoned_cart_email->init();

    expect($registered_actions)->contains('wp');
  }

  public function testItFindsUserByWordPressSession() {
    $this->createNewsletter();
    $this->createSubscriberAsCurrentUser();
    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);

    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();
    expect(ScheduledTask::findMany())->count(1);
  }


  public function testItFindsUserByCookie() {
    $this->createNewsletter();
    $subscriber = $this->createSubscriber();

    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, [
        'exists' => false,
      ])
    );

    $_COOKIE['mailpoet_abandoned_cart_tracking'] = json_encode([
      'subscriber_id' => $subscriber->id,
    ]);

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();
    expect(ScheduledTask::findMany())->count(1);
  }

  public function testItSchedulesEmailWhenItemAddedToCart() {
    $this->createNewsletter();
    $this->createSubscriberAsCurrentUser();

    // ensure tracking started
    $this->page_visit_tracker_mock->expects($this->once())->method('startTracking');

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();

    $expected_time = $this->getExpectedScheduledTime();
    $scheduled_tasks = ScheduledTask::findMany();
    expect($scheduled_tasks)->count(1);
    expect($scheduled_tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduled_tasks[0]->scheduled_at)->same($expected_time->format('Y-m-d H:i:s'));
  }

  public function testItPostponesEmailWhenCartEdited() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduled_in_near_future = clone $this->current_time;
    $scheduled_in_near_future->addMinutes(5);
    $this->createSendingTask($newsletter, $subscriber, $scheduled_in_near_future);

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();

    $expected_time = $this->getExpectedScheduledTime();
    $scheduled_tasks = ScheduledTask::findMany();
    expect($scheduled_tasks)->count(1);
    expect($scheduled_tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduled_tasks[0]->scheduled_at)->same($expected_time->format('Y-m-d H:i:s'));
  }

  public function testItCancelsEmailWhenCartEmpty() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduled_in_future = clone $this->current_time;
    $scheduled_in_future->addHours(2);
    $this->createSendingTask($newsletter, $subscriber, $scheduled_in_future);

    // ensure tracking cancelled
    $this->page_visit_tracker_mock->expects($this->once())->method('stopTracking');

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(true);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();

    expect(ScheduledTask::findMany())->count(0);
    expect(ScheduledTaskSubscriber::findMany())->count(0);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItSchedulesNewEmailWhenEmailAlreadySent() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduled_in_past = clone $this->current_time;
    $scheduled_in_past->addHours(-10);
    $this->createSendingTask($newsletter, $subscriber, $scheduled_in_past);

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->handleCartChange();

    $expected_time = $this->getExpectedScheduledTime();
    expect(ScheduledTask::findMany())->count(2);

    $completed = ScheduledTask::where('status', ScheduledTask::STATUS_COMPLETED)->findOne();
    expect($completed->scheduled_at)->same($scheduled_in_past->format('Y-m-d H:i:s'));

    $scheduled = ScheduledTask::where('status', ScheduledTask::STATUS_SCHEDULED)->findOne();
    expect($scheduled->scheduled_at)->same($expected_time->format('Y-m-d H:i:s'));
  }

  public function testItPostponesEmailWhenPageVisited() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduled_in_near_future = clone $this->current_time;
    $scheduled_in_near_future->addMinutes(5);
    $this->createSendingTask($newsletter, $subscriber, $scheduled_in_near_future);

    // ensure last visit timestamp updated & execute tracking callback
    $this->page_visit_tracker_mock
      ->expects($this->once())
      ->method('trackVisit')
      ->willReturnCallback(function (callable $onTrackCallback) {
        $onTrackCallback();
      });

    $this->woo_commerce_cart_mock->method('is_empty')->willReturn(false);
    $abandoned_cart_email = $this->createAbandonedCartEmail();
    $abandoned_cart_email->init();
    $abandoned_cart_email->trackPageVisit();

    $expected_time = $this->getExpectedScheduledTime();
    $scheduled_tasks = ScheduledTask::findMany();
    expect($scheduled_tasks)->count(1);
    expect($scheduled_tasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduled_tasks[0]->scheduled_at)->same($expected_time->format('Y-m-d H:i:s'));
  }

  private function createAbandonedCartEmail() {
    return $this->make(AbandonedCart::class, [
      'wp' => $this->wp,
      'woo_commerce_helper' => $this->woo_commerce_helper_mock,
      'cookies' => new Cookies(),
      'page_visit_tracker' => $this->page_visit_tracker_mock,
      'scheduler' => new AutomaticEmailScheduler(),
    ]);
  }

  private function createNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_AUTOMATIC;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();

    $this->createNewsletterOptions($newsletter, [
      'group' => WooCommerceEmail::SLUG,
      'event' => AbandonedCart::SLUG,
      'afterTimeType' => 'hours',
      'afterTimeNumber' => self::SCHEDULE_EMAIL_AFTER_HOURS,
      'sendTo' => 'user',
    ]);
    return $newsletter;
  }

  private function createSendingTask(Newsletter $newsletter, Subscriber $subscriber, Carbon $schedule_at) {
    $task = SendingTask::create();
    $task->newsletter_id = $newsletter->id;
    $task->setSubscribers([$subscriber->id]);
    $task->updateProcessedSubscribers([$subscriber->id]);
    $task->save();

    $scheduled_task = $task->task();
    $scheduled_task->scheduled_at = $schedule_at;
    $scheduled_task->status = $this->current_time < $schedule_at
      ? ScheduledTask::STATUS_SCHEDULED
      : ScheduledTask::STATUS_COMPLETED;
    $scheduled_task->save();

    return $task;
  }

  private function createNewsletterOptions(Newsletter $newsletter, array $options) {
    foreach ($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)
        ->where('newsletter_type', $newsletter->type)
        ->findOne();

      if (!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->hydrate([
          'newsletter_type' => $newsletter->type,
          'name' => $option,
        ]);
        $newsletter_option_field->save();
      }

      $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter->id)
        ->where('option_field_id', $newsletter_option_field->id)
        ->findOne();

      if (!$newsletter_option) {
        $newsletter_option = NewsletterOption::create();
        $newsletter_option->hydrate([
          'newsletter_id' => $newsletter->id,
          'option_field_id' => $newsletter_option_field->id,
          'value' => $value,
        ]);
        $newsletter_option->save();
      }
    }
  }

  private function createSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->email = 'subscriber@example.com';
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    $subscriber->wp_user_id = 123;
    return $subscriber->save();
  }

  private function createSubscriberAsCurrentUser() {
    $subscriber = $this->createSubscriber();
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, [
        'ID' => $subscriber->wp_user_id,
        'exists' => true,
      ])
    );
    return $subscriber;
  }

  private function getExpectedScheduledTime() {
    $expected_time = clone $this->current_time;
    $expected_time->addHours(self::SCHEDULE_EMAIL_AFTER_HOURS);
    return $expected_time;
  }

  private function mockWooCommerceClass($class_name, array $methods) {
    // WooCommerce class needs to be mocked without default 'disallowMockingUnknownTypes'
    // since WooCommerce may not be active (would result in error mocking undefined class)
    return $this->getMockBuilder($class_name)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods($methods)
      ->getMock();
  }

  private function cleanup() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }

  public function _after() {
    WPFunctions::set(new WPFunctions());
    Carbon::setTestNow();
    $this->cleanup();
  }
}
