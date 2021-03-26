<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\Config\Populator;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationsScheduler;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SendingQueueTest extends \MailPoetTest {
  public $sendingQueueWorker;
  public $cronHelper;
  public $newsletterLink;
  public $queue;
  public $newsletterSegment;
  public $newsletter;
  public $subscriberSegment;
  public $segment;
  public $subscriber;
  private $mailerTaskDummyResponse = ['response' => true];
  /** @var SendingErrorHandler */
  private $sendingErrorHandler;
  /** @var SettingsController */
  private $settings;
  /** @var StatsNotificationsScheduler */
  private $statsNotificationsWorker;
  /** @var LoggerFactory */
  private $loggerFactory;
  /** @var NewslettersRepository */
  private $newslettersRepository;
  /** @var SubscribersFinder */
  private $subscribersFinder;

  public function _before() {
    parent::_before();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'john@doe.com';
    $this->subscriber->firstName = 'John';
    $this->subscriber->lastName = 'Doe';
    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->source = 'administrator';
    $this->subscriber->save();
    $this->segment = Segment::create();
    $this->segment->name = 'segment';
    $this->segment->save();
    $this->subscriberSegment = SubscriberSegment::create();
    $this->subscriberSegment->subscriberId = $this->subscriber->id;
    $this->subscriberSegment->segmentId = (int)$this->segment->id;
    $this->subscriberSegment->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->status = Newsletter::STATUS_ACTIVE;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->save();
    $this->newsletterSegment = NewsletterSegment::create();
    $this->newsletterSegment->newsletterId = $this->newsletter->id;
    $this->newsletterSegment->segmentId = (int)$this->segment->id;
    $this->newsletterSegment->save();
    $this->queue = SendingTask::create();
    $this->queue->newsletterId = $this->newsletter->id;
    $this->queue->setSubscribers([$this->subscriber->id]);
    $this->queue->countCotal = 1;
    $this->queue->save();
    $this->newsletterLink = NewsletterLink::create();
    $this->newsletterLink->newsletterId = $this->newsletter->id;
    $this->newsletterLink->queueId = $this->queue->id;
    $this->newsletterLink->url = '[link:subscription_instant_unsubscribe_url]';
    $this->newsletterLink->hash = 'abcde';
    $this->newsletterLink->save();
    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
    $this->sendingErrorHandler = new SendingErrorHandler();
    $this->statsNotificationsWorker = Stub::makeEmpty(StatsNotificationsScheduler::class);
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->sendingQueueWorker = $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]));
    $this->newslettersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
  }

  private function getDirectUnsubscribeURL() {
    return SubscriptionUrlFactory::getInstance()->getUnsubscribeUrl($this->subscriber, $this->queue->id);
  }

  private function getTrackedUnsubscribeURL() {
    $linkTokens = new LinkTokens;
    $data = Links::createUrlDataObject(
      $this->subscriber->id,
      $linkTokens->getToken($this->subscriber),
      $this->queue->id,
      $this->newsletterLink->hash,
      false
    );
    return Router::buildRequest(
      Track::ENDPOINT,
      Track::ACTION_CLICK,
      $data
    );
  }

  public function testItConstructs() {
    expect($this->sendingQueueWorker->batchSize)->equals(SendingQueueWorker::BATCH_SIZE);
    expect($this->sendingQueueWorker->mailerTask instanceof MailerTask);
    expect($this->sendingQueueWorker->newsletterTask instanceof NewsletterTask);
  }

  public function testItEnforcesExecutionLimitsBeforeQueueProcessing() {
    $sendingQueueWorker = Stub::make($this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class)),
      [
        'processQueue' => Expected::never(),
        'enforceSendingAndExecutionLimits' => Expected::exactly(1, function() {
          throw new \Exception();
        }),
      ], $this);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      Stub::makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder
    );
    try {
      $sendingQueueWorker->process();
      self::fail('Execution limits function was not called.');
    } catch (\Exception $e) {
      // No exception handling needed
    }
  }

  public function testItEnforcesExecutionLimitsAfterSendingWhenQueueStatusIsNotSetToComplete() {
    $sendingQueueWorker = Stub::make(
      $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class)),
      [
        'enforceSendingAndExecutionLimits' => Expected::exactly(1),
      ], $this);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      Stub::makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      Stub::make(
        new MailerTask(),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      )
    );
    $sendingQueueWorker->sendNewsletters(
      $this->queue,
      $preparedSubscribers = [],
      $preparedNewsletters = [],
      $preparedSubscribers = [],
      $statistics[] = [
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $this->queue->id,
      ],
      microtime(true)
    );
  }

  public function testItDoesNotEnforceExecutionLimitsAfterSendingWhenQueueStatusIsSetToComplete() {
    // when sending is done and there are no more subscribers to process, continue
    // without enforcing execution limits. this allows the newsletter to be marked as sent
    // in the process() method and after that execution limits will be enforced
    $queue = $this->queue;
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $sendingQueueWorker = Stub::make(
      $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class)),
      [
        'enforceSendingAndExecutionLimits' => Expected::never(),
      ], $this);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      Stub::makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      Stub::make(
        new MailerTask(),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      )
    );
    $sendingQueueWorker->sendNewsletters(
      $queue,
      $preparedSubscribers = [],
      $preparedNewsletters = [],
      $preparedSubscribers = [],
      $statistics[] = [
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $queue->id,
      ],
      microtime(true)
    );
  }

  public function testItEnforcesExecutionLimitsAfterQueueProcessing() {
    $sendingQueueWorker = Stub::make(
      $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class)),
      [
        'processQueue' => function() {
          // this function returns a queue object
          return (object)['status' => null, 'taskId' => 0];
        },
        'enforceSendingAndExecutionLimits' => Expected::exactly(2),
      ], $this);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      Stub::makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder
    );
    $sendingQueueWorker->process();
  }

  public function testItDeletesQueueWhenNewsletterIsNotFound() {
    // queue exists
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->notEquals(false);

    // delete newsletter
    $this->newslettersRepository->bulkDelete([$this->newsletter->id]);

    // queue no longer exists
    $this->sendingQueueWorker->process();
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->false();
  }

  public function testItPassesExtraParametersToMailerWhenTrackingIsDisabled() {
    $this->settings->set('tracking.enabled', false);
    $directUnsubscribeURL = $this->getDirectUnsubscribeURL();
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extraParams) use ($directUnsubscribeURL) {
            expect(isset($extraParams['unsubscribe_url']))->true();
            expect($extraParams['unsubscribe_url'])->equals($directUnsubscribeURL);
            expect(isset($extraParams['meta']))->true();
            expect($extraParams['meta'])->equals([
              'email_type' => 'newsletter',
              'subscriber_status' => 'subscribed',
              'subscriber_source' => 'administrator',
            ]);
            return $this->mailerTaskDummyResponse;
          }),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();
  }

  public function testItPassesExtraParametersToMailerWhenTrackingIsEnabled() {
    $this->settings->set('tracking.enabled', true);
    $trackedUnsubscribeURL = $this->getTrackedUnsubscribeURL();
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extraParams) use ($trackedUnsubscribeURL) {
            expect(isset($extraParams['unsubscribe_url']))->true();
            expect($extraParams['unsubscribe_url'])->equals($trackedUnsubscribeURL);
            expect(isset($extraParams['meta']))->true();
            expect($extraParams['meta'])->equals([
              'email_type' => 'newsletter',
              'subscriber_status' => 'subscribed',
              'subscriber_source' => 'administrator',
            ]);
            return $this->mailerTaskDummyResponse;
          }),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();
  }

  public function testItCanProcessSubscribersOneByOne() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extraParams) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->id]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItCanProcessSubscribersInBulk() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'sendBulk' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter[0]['body']['html']))->true();
            expect(!empty($newsletter[0]['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
          'getProcessingMethod' => Expected::exactly(1, function() {
            return 'bulk';
          }),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->id]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItProcessesStandardNewsletters() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // newsletter status is set to sent and sent_at date is populated
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updatedNewsletter->sentAt)->equals($updatedQueue->processedAt);

    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->id]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();

    $this->queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $this->queue->updated_at = $originalUpdated;
    $this->queue->save();

    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class),
      Stub::makeEmpty(new MailerTask(), [], $this)
    );
    $sendingQueueWorker->process();

    $newQueue = ScheduledTask::findOne($this->queue->task_id);
    assert($newQueue instanceof ScheduledTask);
    expect($newQueue->updatedAt)->notEquals($originalUpdated);
  }

  public function testItCanProcessWelcomeNewsletters() {
    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ],
        $this
      )
    );

    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->id]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItPreventsSendingWelcomeEmailWhenSubscriberIsUnsubscribed() {
    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::exactly(0),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);

    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([]);
    expect($updatedQueue->countTotal)->equals(0);
    expect($updatedQueue->countProcessed)->equals(0);
    expect($updatedQueue->countToProcess)->equals(0);
  }

  public function testItRemovesNonexistentSubscribersFromProcessingList() {
    $queue = $this->queue;
    $queue->setSubscribers([
      $this->subscriber->id(),
      12345645454,
    ]);
    $queue->countTotal = 2;
    $queue->save();
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      [
        'send' => Expected::exactly(1, function() {
          return $this->mailerTaskDummyResponse;
        }),
      ],
      $this
    );
    $sendingQueueWorker->process();

    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->id]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    expect(count($statistics))->equals(1);
  }

  public function testItUpdatesQueueSubscriberCountWhenNoneOfSubscribersExist() {
    $queue = $this->queue;
    $queue->setSubscribers([
      123,
      456,
    ]);
    $queue->countTotal = 2;
    $queue->save();
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      ['send' => $this->mailerTaskDummyResponse]
    );
    $sendingQueueWorker->process();

    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([]);
    expect($updatedQueue->countTotal)->equals(0);
    expect($updatedQueue->countProcessed)->equals(0);
    expect($updatedQueue->countToProcess)->equals(0);
  }

  public function testItDoesNotSendToTrashedSubscribers() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is sent to existing subscriber
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(1);

    // newsletter is not sent to trashed subscriber
    $this->_after();
    $this->_before();
    $subscriber = $this->subscriber;
    $subscriber->deletedAt = Carbon::now();
    $subscriber->save();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItDoesNotSendToGloballyUnsubscribedSubscribers() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to globally unsubscribed subscriber
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->save();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItDoesNotSendToSubscribersUnsubscribedFromSegments() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to subscriber unsubscribed from segment
    $subscriberSegment = $this->subscriberSegment;
    $subscriberSegment->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriberSegment->save();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItDoesNotSendToInactiveSubscribers() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = Stub::make(
      new MailerTask(),
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to inactive subscriber
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_INACTIVE;
    $subscriber->save();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItPausesSendingWhenProcessedSubscriberListCannotBeUpdated() {
    $sendingTask = $this->createMock(SendingTask::class);
    $sendingTask
      ->method('updateProcessedSubscribers')
      ->will($this->returnValue(false));
    $sendingTask
      ->method('__get')
      ->with('id')
      ->will($this->returnValue(100));
    $sendingQueueWorker = Stub::make(
      $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class))
    );
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      Stub::makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      Stub::make(
        new MailerTask(),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      )
    );
    try {
      $sendingQueueWorker->sendNewsletters(
        $sendingTask,
        $preparedSubscribers = [],
        $preparedNewsletters = [],
        $preparedSubscribers = [],
        $statistics = [],
        microtime(true)
      );
      $this->fail('Paused sending exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
    $mailerLog = MailerLog::getMailerLog();
    expect($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    expect($mailerLog['error'])->equals(
      [
        'operation' => 'processed_list_update',
        'error_message' => 'QUEUE-100-PROCESSED-LIST-UPDATE',
      ]
    );
  }

  public function testItDoesNotUpdateNewsletterHashDuringSending() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      Stub::make(
        new MailerTask(),
        [
          'send' => Expected::once($this->mailerTaskDummyResponse),
        ],
        $this
      )
    );
    $sendingQueueWorker->process();

    // newsletter is sent and hash remains intact
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updatedNewsletter->hash)->equals($this->newsletter->hash);
  }

  public function testItAllowsSettingCustomBatchSize() {
    $customBatchSizeValue = 10;
    $filter = function() use ($customBatchSizeValue) {
      return $customBatchSizeValue;
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
    $sendingQueueWorker = $this->getSendingQueueWorker(Stub::makeEmpty(NewslettersRepository::class));
    expect($sendingQueueWorker->batchSize)->equals($customBatchSizeValue);
    $wp->removeFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
  }

  public function testItReschedulesBounceTaskWhenPlannedInFarFuture() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMonths(1),
    ]);

    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      $this->make(new MailerTask(), [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    assert($refetchedTask instanceof ScheduledTask); // PHPStan
    expect($refetchedTask->scheduledAt)->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(42));
  }

  public function testDoesNoRescheduleBounceTaskWhenPlannedInNearFuture() {
    $inOneHour = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1);
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => $inOneHour,
    ]);

    $sendingQueueWorker = $this->getSendingQueueWorker(
      Stub::makeEmpty(NewslettersRepository::class, ['findOneById' => new NewsletterEntity()]),
      $this->make(new MailerTask(), [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    assert($refetchedTask instanceof ScheduledTask); // PHPStan
    expect($refetchedTask->scheduledAt)->equals($inOneHour);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    $this->truncateEntity(NewsletterPostEntity::class);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsNewsletters::$_table);
  }

  private function getSendingQueueWorker($newsletterRepositoryMock = null, $mailerMock = null) {
    return new SendingQueueWorker(
      $this->sendingErrorHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $newsletterRepositoryMock ?: $this->newslettersRepository,
      $this->cronHelper,
      $this->subscribersFinder,
      $mailerMock
    );
  }
}
