<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\Track\SubscriberActivityTracker;
use MailPoet\Statistics\Track\SubscriberCookie;
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
  private $currentTime;

  /** @var WPFunctions&MockObject */
  private $wp;

  /** @var WooCommerce|MockObject */
  private $wooCommerceMock;

  /** @var WC_Cart|MockObject */
  private $wooCommerceCartMock;

  /** @var WooCommerceHelper|MockObject */
  private $wooCommerceHelperMock;

  /** @var SubscriberActivityTracker&MockObject */
  private $subscriberActivityTrackerMock;

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  public function _before() {
    $this->cleanup();

    $this->currentTime = Carbon::createFromTimestamp((new WPFunctions())->currentTime('timestamp'));
    Carbon::setTestNow($this->currentTime);
    $this->subscriberActivityTrackerMock = $this->createMock(SubscriberActivityTracker::class);

    /** @var WPFunctions|MockObject $wp - for phpstan */
    $wp = $this->makeEmpty(WPFunctions::class, [
      'currentTime' => function ($arg) {
        if ($arg === 'timestamp') {
          return $this->currentTime->getTimestamp();
        } elseif ($arg === 'mysql') {
          return $this->currentTime->format('Y-m-d H:i:s');
        }
      },
    ]);
    $this->wp = $wp;
    WPFunctions::set($this->wp);

    $this->automaticEmailScheduler = new AutomaticEmailScheduler(new Scheduler($this->wp));

    $this->wooCommerceMock = $this->mockWooCommerceClass(WooCommerce::class, []);
    $this->wooCommerceCartMock = $this->mockWooCommerceClass(WC_Cart::class, ['is_empty', 'get_cart']);
    $this->wooCommerceMock->cart = $this->wooCommerceCartMock;
    /** @var WooCommerceHelper|MockObject $wooCommerceHelperMock - for phpstan */
    $wooCommerceHelperMock = $this->make(WooCommerceHelper::class, [
      'isWooCommerceActive' => true,
      'WC' => $this->wooCommerceMock,
    ]);
    $this->wooCommerceHelperMock = $wooCommerceHelperMock;
  }

  public function testItGetsEventDetails() {
    $settings = $this->diContainer->get(SettingsController::class);
    $wp = new WPFunctions();
    $wcHelper = new WooCommerceHelper();
    $cookies = new Cookies();
    $subscriberCookie = new SubscriberCookie($cookies, new TrackingConfig($settings));
    $event = new AbandonedCart(
      $wp,
      $wcHelper,
      $subscriberCookie,
      $this->diContainer->get(SubscriberActivityTracker::class),
      $this->diContainer->get(AutomaticEmailScheduler::class)
    );
    $result = $event->getEventDetails();
    expect($result)->notEmpty();
    expect($result['slug'])->equals(AbandonedCart::SLUG);
  }

  public function testItRegistersWooCommerceCartEvents() {
    $abandonedCartEmail = $this->createAbandonedCartEmail();

    $registeredActions = [];
    $this->wp->method('addAction')->willReturnCallback(function ($name) use (&$registeredActions) {
      $registeredActions[] = $name;
    });
    $abandonedCartEmail->init();

    expect($registeredActions)->contains('woocommerce_add_to_cart');
    expect($registeredActions)->contains('woocommerce_cart_item_removed');
    expect($registeredActions)->contains('woocommerce_after_cart_item_quantity_update');
    expect($registeredActions)->contains('woocommerce_before_cart_item_quantity_zero');
    expect($registeredActions)->contains('woocommerce_cart_emptied');
    expect($registeredActions)->contains('woocommerce_cart_item_restored');
  }

  public function testItRegistersToSubscriberActivityEvent() {
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $this->subscriberActivityTrackerMock
      ->expects($this->once())
      ->method('registerCallback');
    $abandonedCartEmail->init();
  }

  public function testItFindsUserByWordPressSession() {
    $this->createNewsletter();
    $this->createSubscriberAsCurrentUser();
    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);

    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();
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

    $_COOKIE['mailpoet_subscriber'] = json_encode([
      'subscriber_id' => $subscriber->id,
    ]);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();
    expect(ScheduledTask::findMany())->count(1);
  }

  public function testItSchedulesEmailWhenItemAddedToCart() {
    $this->createNewsletter();
    $this->createSubscriberAsCurrentUser();

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $this->wooCommerceCartMock->method('get_cart')->willReturn([
      ['product_id' => 123], ['product_id' => 456], // dummy product IDs
    ]);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();

    $expectedTime = $this->getExpectedScheduledTime();
    $scheduledTasks = ScheduledTask::findMany();
    expect($scheduledTasks)->count(1);
    expect($scheduledTasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduledTasks[0]->scheduled_at)->same($expectedTime->format('Y-m-d H:i:s'));
    /** @var SendingQueue $sendingQueue */
    $sendingQueue = SendingQueue::where('task_id', $scheduledTasks[0]->id)->findOne();
    expect($sendingQueue->getMeta())->same([AbandonedCart::TASK_META_NAME => [123, 456]]);
  }

  public function testItPostponesEmailWhenCartEdited() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduledInNearFuture = clone $this->currentTime;
    $scheduledInNearFuture->addMinutes(5);
    $this->createSendingTask($newsletter, $subscriber, $scheduledInNearFuture);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();

    $expectedTime = $this->getExpectedScheduledTime();
    $scheduledTasks = ScheduledTask::findMany();
    expect($scheduledTasks)->count(1);
    expect($scheduledTasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduledTasks[0]->scheduled_at)->same($expectedTime->format('Y-m-d H:i:s'));
  }

  public function testItCancelsEmailWhenCartEmpty() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduledInFuture = clone $this->currentTime;
    $scheduledInFuture->addHours(2);
    $this->createSendingTask($newsletter, $subscriber, $scheduledInFuture);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(true);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();

    expect(ScheduledTask::findMany())->count(0);
    expect(ScheduledTaskSubscriber::findMany())->count(0);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItSchedulesNewEmailWhenEmailAlreadySent() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduledInPast = clone $this->currentTime;
    $scheduledInPast->addHours(-10);
    $this->createSendingTask($newsletter, $subscriber, $scheduledInPast);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();

    $expectedTime = $this->getExpectedScheduledTime();
    expect(ScheduledTask::findMany())->count(2);

    $completed = ScheduledTask::where('status', ScheduledTask::STATUS_COMPLETED)->findOne();
    assert($completed instanceof ScheduledTask);
    expect($completed->scheduledAt)->same($scheduledInPast->format('Y-m-d H:i:s'));

    $scheduled = ScheduledTask::where('status', ScheduledTask::STATUS_SCHEDULED)->findOne();
    assert($scheduled instanceof ScheduledTask);
    expect($scheduled->scheduledAt)->same($expectedTime->format('Y-m-d H:i:s'));
  }

  public function testItPostponesEmailWhenSubscriberIsActiveOnSite() {
    $newsletter = $this->createNewsletter();
    $subscriber = $this->createSubscriberAsCurrentUser();

    $scheduledInNearFuture = clone $this->currentTime;
    $scheduledInNearFuture->addMinutes(5);
    $this->createSendingTask($newsletter, $subscriber, $scheduledInNearFuture);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $subscriberEntity = $this->entityManager->find(SubscriberEntity::class, $subscriber->id);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberEntity);
    $abandonedCartEmail->handleSubscriberActivity($subscriberEntity);

    $expectedTime = $this->getExpectedScheduledTime();
    $scheduledTasks = ScheduledTask::findMany();
    expect($scheduledTasks)->count(1);
    expect($scheduledTasks[0]->status)->same(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduledTasks[0]->scheduled_at)->same($expectedTime->format('Y-m-d H:i:s'));
  }

  private function createAbandonedCartEmail() {
    $settings = $this->diContainer->get(SettingsController::class);
    $automaticEmailScheduler = $this->automaticEmailScheduler;

    return $this->make(AbandonedCart::class, [
      'wp' => $this->wp,
      'wooCommerceHelper' => $this->wooCommerceHelperMock,
      'subscriberCookie' => new SubscriberCookie(new Cookies(), new TrackingConfig($settings)),
      'subscriberActivityTracker' => $this->subscriberActivityTrackerMock,
      'scheduler' => $automaticEmailScheduler,
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

  private function createSendingTask(Newsletter $newsletter, Subscriber $subscriber, Carbon $scheduleAt) {
    $task = SendingTask::create();
    $task->newsletterId = $newsletter->id;
    $task->setSubscribers([$subscriber->id]);
    $task->updateProcessedSubscribers([$subscriber->id]);
    $task->save();

    $scheduledTask = $task->task();
    $scheduledTask->scheduledAt = $scheduleAt;
    $scheduledTask->status = $this->currentTime < $scheduleAt
      ? ScheduledTask::STATUS_SCHEDULED
      : ScheduledTask::STATUS_COMPLETED;
    $scheduledTask->save();

    return $task;
  }

  private function createNewsletterOptions(Newsletter $newsletter, array $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)
        ->where('newsletter_type', $newsletter->type)
        ->findOne();

      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->hydrate([
          'newsletter_type' => $newsletter->type,
          'name' => $option,
        ]);
        $newsletterOptionField->save();
      }

      $newsletterOption = NewsletterOption::where('newsletter_id', $newsletter->id)
        ->where('option_field_id', $newsletterOptionField->id)
        ->findOne();

      if (!$newsletterOption) {
        $newsletterOption = NewsletterOption::create();
        $newsletterOption->hydrate([
          'newsletter_id' => $newsletter->id,
          'option_field_id' => $newsletterOptionField->id,
          'value' => $value,
        ]);
        $newsletterOption->save();
      }
    }
  }

  private function createSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->email = 'subscriber@example.com';
    $subscriber->firstName = 'First';
    $subscriber->lastName = 'Last';
    $subscriber->wpUserId = 123;
    return $subscriber->save();
  }

  private function createSubscriberAsCurrentUser() {
    $subscriber = $this->createSubscriber();
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, [
        'ID' => $subscriber->wpUserId,
        'exists' => true,
      ])
    );
    return $subscriber;
  }

  private function getExpectedScheduledTime() {
    $expectedTime = clone $this->currentTime;
    $expectedTime->addHours(self::SCHEDULE_EMAIL_AFTER_HOURS);
    return $expectedTime;
  }

  /**
   * @param class-string<WooCommerce|WC_Cart> $className
   */
  private function mockWooCommerceClass($className, array $methods) {
    // WooCommerce class needs to be mocked without default 'disallowMockingUnknownTypes'
    // since WooCommerce may not be active (would result in error mocking undefined class)
    return $this->getMockBuilder($className)
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
