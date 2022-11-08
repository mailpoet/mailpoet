<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\Config\Renderer;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\AssetsController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\CaptchaFormRenderer;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\Subscription\Pages;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class PagesTest extends \MailPoetTest {
  private $testData = [];

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->subscriber = new SubscriberEntity();
    $this->subscriber->setEmail('jane.doe@example.com');
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->persist($this->subscriber);
    $this->subscribersRepository->flush();
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $this->testData['email'] = $this->subscriber->getEmail();
    $this->testData['token'] = $linkTokens->getToken($this->subscriber);
  }

  public function testItConfirmsSubscription() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['sendWithSubscriberAndSegmentEntities' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init(false, $this->testData, false, false);
    $subscription->confirm();

    $confirmedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $confirmedSubscriber);
    expect($confirmedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->assertTrue(Carbon::parse($confirmedSubscriber->getLastSubscribedAt())->isToday());
  }

  public function testItUpdatesUnconfirmedDataWhenConfirmingSubscription() {
    $firstName = 'Jane';
    $lastName = 'Doe';
    $this->subscriber->setUnconfirmedData(
      (string)json_encode(['first_name' => $firstName, 'last_name' => $lastName, 'email' => 'jane.doe@example.com'])
    );
    $this->entityManager->persist($this->subscriber);
    $this->entityManager->flush();

    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['sendWithSubscriberAndSegmentEntities' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init(false, $this->testData, false, false);

    $subscription->confirm();

    $confirmedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $confirmedSubscriber);
    $this->assertSame(SubscriberEntity::STATUS_SUBSCRIBED, $confirmedSubscriber->getStatus());
    $this->assertSame($firstName, $confirmedSubscriber->getFirstName());
    $this->assertSame($lastName, $confirmedSubscriber->getLastName());
    $this->assertNull($confirmedSubscriber->getUnconfirmedData());
  }

  public function testItUpdatesSubscriptionOnDuplicateAttemptButDoesntSendNotification() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::never()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscriber = $this->subscriber;
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setFirstName('First name');
    $subscriber->setUnconfirmedData(null);
    $subscriber->setLastSubscribedAt(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subDays(10));
    $subscriber->setConfirmedIp(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subDays(10));
    $this->entityManager->flush();
    $subscription = $pages->init(false, $this->testData, false, false);
    $subscription->confirm();
    $this->entityManager->clear();
    $confirmedSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $confirmedSubscriber);
    expect($confirmedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->getConfirmedAt())->greaterOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subSecond());
    expect($confirmedSubscriber->getConfirmedAt())->lessOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addSecond());
    expect($confirmedSubscriber->getLastSubscribedAt())->greaterOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subSecond());
    expect($confirmedSubscriber->getLastSubscribedAt())->lessOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addSecond());
    expect($confirmedSubscriber->getFirstName())->equals('First name');
  }

  public function testItSendsWelcomeNotificationUponConfirmingSubscription() {
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['sendWithSubscriberAndSegmentEntities' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init($action = false, $this->testData, false, false);

    $segment = $this->createSegment();
    $this->createSubscriberSegment($segment);

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Some subject');
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->entityManager->persist($newsletter);

    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->getId(),
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    (new NewsletterOptionFactory())->createMultipleOptions($newsletter, $newsletterOptions);

    // confirm subscription and ensure that welcome email is scheduled
    $subscription->confirm();
    $newsletterEntity = $newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $scheduledNotifications = $scheduledTasksRepository->findByNewsletterAndStatus($newsletterEntity, SendingQueueEntity::STATUS_SCHEDULED);
    expect(count($scheduledNotifications))->equals(1);

    // Does not schedule another on repeated confirmation
    $subscription->confirm();
    $scheduledNotifications = $scheduledTasksRepository->findByNewsletterAndStatus($newsletterEntity, SendingQueueEntity::STATUS_SCHEDULED);
    expect(count($scheduledNotifications))->equals(1);
  }

  public function testItUnsubscribes() {
    $segment = $this->createSegment();
    $this->createSubscriberSegment($segment);

    $pages = $this->getPages()->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe(StatisticsUnsubscribeEntity::METHOD_LINK);

    $updatedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $subscriberSegments = $updatedSubscriber->getSubscriberSegments();
    foreach ($subscriberSegments as $subscriberSegment) {
      $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberSegment->getStatus());
    }
  }

  public function testItTrackUnsubscribeWhenTrackingIsEnabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::once()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe(StatisticsUnsubscribeEntity::METHOD_LINK);
  }

  public function testItDontTrackUnsubscribeWhenTrackingIsDisabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::never()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe(StatisticsUnsubscribeEntity::METHOD_LINK);
  }

  public function testItDoesntUnsubscribeWhenPreviewing() {
    $this->testData['preview'] = 1;
    $pages = $this->getPages()->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe(StatisticsUnsubscribeEntity::METHOD_LINK);

    $updatedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getStatus())->notEquals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(StatisticsUnsubscribeEntity::class);
  }

  private function getPages(
    NewSubscriberNotificationMailer $newSubscriberNotificationsMock = null,
    Unsubscribes $unsubscribesMock = null
  ): Pages {
    $container = ContainerWrapper::getInstance();
    return new Pages(
      $newSubscriberNotificationsMock ?? $container->get(NewSubscriberNotificationMailer::class),
      $container->get(WPFunctions::class),
      $container->get(CaptchaFormRenderer::class),
      $container->get(WelcomeScheduler::class),
      $container->get(LinkTokens::class),
      $container->get(SubscriptionUrlFactory::class),
      $container->get(AssetsController::class),
      $container->get(Renderer::class),
      $unsubscribesMock ?? $container->get(Unsubscribes::class),
      $container->get(ManageSubscriptionFormRenderer::class),
      $container->get(SubscriberHandler::class),
      $this->subscribersRepository,
      $container->get(TrackingConfig::class),
      $container->get(EntityManager::class),
      $container->get(SubscriberSaveController::class),
      $container->get(SubscriberSegmentRepository::class)
    );
  }

  private function createSegment(): SegmentEntity {
    $segmentFactory = new SegmentFactory();
    $segment = $segmentFactory->withName('List #1')->create();

    return $segment;
  }

  private function createSubscriberSegment(SegmentEntity $segment) {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $this->subscriber, 'subscribed');
    $this->entityManager->persist($subscriberSegment);
    $this->subscriber->getSubscriberSegments()->add($subscriberSegment);
    $this->entityManager->persist($this->subscriber);
    $this->entityManager->flush();
  }
}
