<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use Codeception\Util\Fixtures;
use MailPoet\Config\Renderer;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\AssetsController;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscription\CaptchaRenderer;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\Subscription\Pages;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class PagesTest extends \MailPoetTest {
  private $testData = [];

  /** @var Subscriber */
  private $subscriber;

  public function _before() {
    parent::_before();
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $this->subscriber->save();
    $linkTokens = new LinkTokens;
    expect($this->subscriber->getErrors())->false();
    $this->testData['email'] = $this->subscriber->email;
    $this->testData['token'] = $linkTokens->getToken($this->subscriber);
  }

  public function testItConfirmsSubscription() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::once()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscription = $pages->init($action = false, $this->testData, false, false);
    $subscription->confirm();
    $confirmedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($confirmedSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->lastSubscribedAt)->greaterOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->subSecond());
    expect($confirmedSubscriber->lastSubscribedAt)->lessOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->addSecond());
  }

  public function testItUpdatesSubscriptionOnDuplicateAttemptButDoesntSendNotification() {
    $newSubscriberNotificationSender = $this->makeEmpty(NewSubscriberNotificationMailer::class, ['send' => Stub\Expected::never()]);
    $pages = $this->getPages($newSubscriberNotificationSender);
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->firstName = 'First name';
    $subscriber->unconfirmedData = '{"first_name" : "Updated first name", "email" : "' . $this->subscriber->email . '"}';
    $subscriber->lastSubscribedAt = Carbon::now()->subDays(10);
    $subscriber->confirmedAt = Carbon::now()->subDays(10);
    $subscriber->save();
    $subscription = $pages->init($action = false, $this->testData, false, false);
    $subscription->confirm();
    $confirmedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($confirmedSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($confirmedSubscriber->confirmedAt)->greaterOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->subSecond());
    expect($confirmedSubscriber->confirmedAt)->lessOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->addSecond());
    expect($confirmedSubscriber->lastSubscribedAt)->greaterOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->subSecond());
    expect($confirmedSubscriber->lastSubscribedAt)->lessOrEquals(Carbon::createFromTimestamp((int)current_time('timestamp'))->addSecond());
    expect($confirmedSubscriber->firstName)->equals('Updated first name');
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
        'subscriber_id' => $this->subscriber->id,
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
    $updatedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($updatedSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItTrackUnsubscribeWhenTrackingIsEnabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::once()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.enabled', 1);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
  }

  public function testItDontTrackUnsubscribeWhenTrackingIsDisabled() {
    $unsubscribesMock = $this->make(Unsubscribes::class, ['track' => Stub\Expected::never()]);
    $this->testData['queueId'] = 1;
    SettingsController::getInstance()->set('tracking.enabled', 0);
    $pages = $this->getPages(null, $unsubscribesMock)->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
  }

  public function testItDoesntUnsubscribeWhenPreviewing() {
    $this->testData['preview'] = 1;
    $pages = $this->getPages()->init($action = 'unsubscribe', $this->testData);
    $pages->unsubscribe();
    $updatedSubscriber = Subscriber::findOne($this->subscriber->id);
    expect($updatedSubscriber->status)->notEquals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsUnsubscribes::$_table);
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
      $container->get(ManageSubscriptionFormRenderer::class)
    );
  }
}
