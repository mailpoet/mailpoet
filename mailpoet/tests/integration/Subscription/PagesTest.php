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
use MailPoet\Features\FeaturesController;
use MailPoet\Form\AssetsController;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\CaptchaRenderer;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\Subscription\Pages;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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
    $this->subscriber->setFirstName('John');
    $this->subscriber->setLastName('John');
    $this->subscriber->setEmail('john.doe@example.com');
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->persist($this->subscriber);
    $this->subscribersRepository->flush();
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $this->testData['email'] = $this->subscriber->getEmail();
    $this->testData['token'] = $linkTokens->getToken($this->subscriber);
  }

  public function testItConfirmsSubscription() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init(false, $this->testData, false, false);
    $subscription->confirm();

    // make sure Doctrine gets the information about the subscriber from the database.
    // this can be removed once Pages is migrated to Doctrine as well
    $this->entityManager->detach($this->subscriber);

    $confirmedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $confirmedSubscriber);
    expect($confirmedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->getLastSubscribedAt())->greaterOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subSecond());
    expect($confirmedSubscriber->getLastSubscribedAt())->lessOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addSecond());
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
    assert($confirmedSubscriber instanceof SubscriberEntity);
    expect($confirmedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->getConfirmedAt())->greaterOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subSecond());
    expect($confirmedSubscriber->getConfirmedAt())->lessOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addSecond());
    expect($confirmedSubscriber->getLastSubscribedAt())->greaterOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subSecond());
    expect($confirmedSubscriber->getLastSubscribedAt())->lessOrEquals(Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addSecond());
    expect($confirmedSubscriber->getFirstName())->equals('First name');
  }

  public function testItSendsWelcomeNotificationUponConfirmingSubscription() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init($action = false, $this->testData, false, false);
    // create segment
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();
    expect($segment->getErrors())->false();
    // create subscriber->segment relation
    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->hydrate(
      [
        'subscriber_id' => $this->subscriber->getId(),
        'segment_id' => $segment->id,
      ]
    );
    $subscriberSegment->save();
    expect($subscriberSegment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    foreach ($newsletterOptions as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = $newsletter->type;
      $newsletterOptionField->save();
      expect($newsletterOptionField->getErrors())->false();

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletter->id;
      $newsletterOption->value = (string)$value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }

    // confirm subscription and ensure that welcome email is scheduled
    $subscription->confirm();
    $scheduledNotifications = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
    expect(count($scheduledNotifications))->equals(1);
    // Does not schedule another on repeated confirmation
    $subscription->confirm();
    $scheduledNotifications = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findMany();
    expect(count($scheduledNotifications))->equals(1);
  }

  public function testItUnsubscribes() {
    $pages = $this->getPages()->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();

    // make sure Doctrine gets the information about the subscriber from the database.
    // this can be removed once Pages is migrated to Doctrine as well
    $this->entityManager->detach($this->subscriber);

    $updatedSubscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItTrackUnsubscribeWhenTrackingIsEnabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::once()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
  }

  public function testItDontTrackUnsubscribeWhenTrackingIsDisabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::never()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
  }

  public function testItDoesntUnsubscribeWhenPreviewing() {
    $this->testData['preview'] = 1;
    $pages = $this->getPages()->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();

    // make sure Doctrine gets the information about the subscriber from the database.
    // this can be removed once Pages is migrated to Doctrine as well
    $this->entityManager->detach($this->subscriber);

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
      $container->get(SettingsController::class),
      $container->get(CaptchaRenderer::class),
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
      $container->get(FeaturesController::class)
    );
  }
}
