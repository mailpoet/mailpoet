<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\Track\SubscriberActivityTracker;
use MailPoet\Statistics\Track\SubscriberCookie;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Cart;
use WooCommerce;
use WP_User;

/**
 * @group woo
 */
class AbandonedCartTest extends \MailPoetTest {
  const SCHEDULE_EMAIL_AFTER_HOURS = 5;

  /** @var Carbon */
  private $currentTime;

  /** @var WPFunctions&MockObject */
  private $wp;

  /** @var \WooCommerce */
  private $wooCommerce;

  /** @var WC_Cart|MockObject */
  private $wooCommerceCartMock;

  /** @var WooCommerceHelper|MockObject */
  private $wooCommerceHelperMock;

  /** @var SubscriberActivityTracker&MockObject */
  private $subscriberActivityTrackerMock;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var AutomaticEmailScheduler */
  private $automaticEmailScheduler;

  /** @var WC_Cart */
  private $cartBackup;

  public function _before() {
    global $woocommerce;
    $this->wooCommerce = $woocommerce;

    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);

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

    $this->automaticEmailScheduler = $this->diContainer->get(AutomaticEmailScheduler::class);

    $this->wooCommerceCartMock = $this->mockWooCommerceClass(WC_Cart::class, ['is_empty', 'get_cart']);
    $this->cartBackup = $this->wooCommerce->cart;
    $this->wooCommerce->cart = $this->wooCommerceCartMock;
    /** @var WooCommerceHelper|MockObject $wooCommerceHelperMock - for phpstan */
    $wooCommerceHelperMock = $this->make(WooCommerceHelper::class, [
      'isWooCommerceActive' => true,
      'WC' => $this->wooCommerce,
    ]);
    $this->wooCommerceHelperMock = $wooCommerceHelperMock;
  }

  public function testItGetsEventDetails() {
    $settings = $this->diContainer->get(SettingsController::class);
    $wp = new WPFunctions();
    $wcHelper = new WooCommerceHelper($wp);
    $cookies = new Cookies();
    $subscriberCookie = new SubscriberCookie($cookies, new TrackingConfig($settings));
    $event = new AbandonedCart(
      $wp,
      $wcHelper,
      $subscriberCookie,
      $this->diContainer->get(SubscriberActivityTracker::class),
      $this->diContainer->get(AutomaticEmailScheduler::class),
      $this->diContainer->get(SubscribersRepository::class)
    );
    $result = $event->getEventDetails();
    $this->assertNotEmpty($result);
    $this->assertEquals($result['slug'], AbandonedCart::SLUG);
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
    $this->assertCount(1, $this->scheduledTasksRepository->findAll());
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
      'subscriber_id' => $subscriber->getId(),
    ]);

    $this->wooCommerceCartMock->method('is_empty')->willReturn(false);
    $abandonedCartEmail = $this->createAbandonedCartEmail();
    $abandonedCartEmail->init();
    $abandonedCartEmail->handleCartChange();
    $this->assertCount(1, $this->scheduledTasksRepository->findAll());
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
    $scheduledTasks = $this->scheduledTasksRepository->findAll();
    $this->assertCount(1, $scheduledTasks);
    $this->assertEquals($scheduledTasks[0]->getStatus(), ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($scheduledTasks[0]->getScheduledAt(), $expectedTime, 1);
    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['task' => $scheduledTasks[0]]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->assertEquals($sendingQueue->getMeta(), [AbandonedCart::TASK_META_NAME => [123, 456]]);
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
    $this->entityManager->clear();
    $scheduledTasks = $this->scheduledTasksRepository->findAll();
    $this->assertCount(1, $scheduledTasks);
    $this->assertEquals($scheduledTasks[0]->getStatus(), ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($scheduledTasks[0]->getScheduledAt(), $expectedTime, 1);
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

    $this->assertCount(0, $this->scheduledTasksRepository->findAll());
    $this->assertCount(0, $this->scheduledTaskSubscribersRepository->findAll());
    $this->assertCount(0, $this->sendingQueuesRepository->findAll());
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
    $this->entityManager->clear();
    $this->assertCount(2, $this->scheduledTasksRepository->findAll());

    $completed = $this->scheduledTasksRepository->findOneBy(['status' => ScheduledTaskEntity::STATUS_COMPLETED]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $completed);
    $this->tester->assertEqualDateTimes($completed->getScheduledAt(), $scheduledInPast, 1);

    $scheduled = $this->scheduledTasksRepository->findOneBy(['status' => ScheduledTaskEntity::STATUS_SCHEDULED]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduled);
    $this->tester->assertEqualDateTimes($scheduled->getScheduledAt(), $expectedTime, 1);
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
    $subscriberEntity = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberEntity);
    $abandonedCartEmail->handleSubscriberActivity($subscriberEntity);

    $expectedTime = $this->getExpectedScheduledTime();
    $this->entityManager->clear();
    $scheduledTasks = $this->scheduledTasksRepository->findAll();
    $this->assertCount(1, $scheduledTasks);
    $this->assertEquals($scheduledTasks[0]->getStatus(), ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tester->assertEqualDateTimes($scheduledTasks[0]->getScheduledAt(), $expectedTime, 1);
  }

  private function createAbandonedCartEmail() {
    $settings = $this->diContainer->get(SettingsController::class);
    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $automaticEmailScheduler = $this->automaticEmailScheduler;

    return $this->make(AbandonedCart::class, [
      'wp' => $this->wp,
      'wooCommerceHelper' => $this->wooCommerceHelperMock,
      'subscriberCookie' => new SubscriberCookie(new Cookies(), new TrackingConfig($settings)),
      'subscriberActivityTracker' => $this->subscriberActivityTrackerMock,
      'scheduler' => $automaticEmailScheduler,
      'subscribersRepository' => $subscribersRepository,
    ]);
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_AUTOMATIC)
      ->withActiveStatus()
      ->create();

    (new NewsletterOptionFactory())->createMultipleOptions($newsletter, [
      'group' => WooCommerceEmail::SLUG,
      'event' => AbandonedCart::SLUG,
      'afterTimeType' => 'hours',
      'afterTimeNumber' => self::SCHEDULE_EMAIL_AFTER_HOURS,
      'sendTo' => 'user',
    ]);
    return $newsletter;
  }

  private function createSendingTask(NewsletterEntity $newsletter, SubscriberEntity $subscriber, Carbon $scheduleAt): ScheduledTaskEntity {
    $scheduledTask = new ScheduledTaskEntity();
    $scheduledTask->setType(SendingTask::TASK_TYPE);
    $this->entityManager->persist($scheduledTask);
    $this->entityManager->flush();

    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setNewsletter($newsletter);
    $sendingQueue->setTask($scheduledTask);
    $this->entityManager->persist($sendingQueue);
    $this->entityManager->flush();

    $sendingQueueSubscriber = new ScheduledTaskSubscriberEntity($scheduledTask, $subscriber, ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    $this->entityManager->persist($sendingQueueSubscriber);
    $this->entityManager->flush();

    $scheduledTask->setScheduledAt($scheduleAt);
    $scheduledTask->setSendingQueue($sendingQueue);
    $scheduledTask->setStatus(($this->currentTime < $scheduleAt) ? ScheduledTaskEntity::STATUS_SCHEDULED : ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->flush();

    return $scheduledTask;
  }

  private function createSubscriber(): SubscriberEntity {
    return (new SubscriberFactory())
      ->withWpUserId(123)
      ->create();
  }

  private function createSubscriberAsCurrentUser(): SubscriberEntity {
    $subscriber = $this->createSubscriber();
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, [
        'ID' => $subscriber->getWpUserId(),
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

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions());
    Carbon::setTestNow();
    // Restore original cart object
    $this->wooCommerce->cart = $this->cartBackup;
  }
}
