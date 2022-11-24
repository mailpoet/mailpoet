<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\Config\Populator;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as TasksLinks;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationsScheduler;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Models\Newsletter;
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
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingQueueTest extends \MailPoetTest {
  /** @var SendingQueueWorker */
  public $sendingQueueWorker;
  public $cronHelper;
  public $newsletterLink;
  /* @var SendingTask */
  public $queue;
  public $newsletterSegment;
  public $newsletter;
  public $subscriberSegment;
  public $segment;
  /** @var SubscriberEntity */
  public $subscriber;
  private $mailerTaskDummyResponse = ['response' => true];
  /** @var SendingThrottlingHandler */
  private $sendingThrottlingHandler;
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
  /** @var SegmentsRepository */
  private $segmentsRepository;
  /** @var WPFunctions */
  private $wp;
  /** @var TasksLinks */
  private $tasksLinks;
  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;
  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->subscriber = $this->createSubscriber('john@doe.com', 'John', 'Doe');
    $this->segment = Segment::create();
    $this->segment->name = 'segment';
    $this->segment->save();
    $this->subscriberSegment = SubscriberSegment::create();
    $this->subscriberSegment->subscriberId = (int)$this->subscriber->getId();
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
    $this->queue->setSubscribers([$this->subscriber->getId()]);
    $this->queue->countTotal = 1;
    $this->queue->save();

    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);

    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $queue = $newsletterEntity->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->newsletterLink = new NewsletterLinkEntity(
      $newsletterEntity,
      $queue,
      '[link:subscription_instant_unsubscribe_url]',
      'abcde'
    );
    $this->entityManager->persist($this->newsletterLink);
    $this->entityManager->flush();

    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
    $this->sendingErrorHandler = $this->diContainer->get(SendingErrorHandler::class);
    $this->sendingThrottlingHandler = $this->diContainer->get(SendingThrottlingHandler::class);
    $this->statsNotificationsWorker = $this->makeEmpty(StatsNotificationsScheduler::class);
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->tasksLinks = $this->diContainer->get(TasksLinks::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->sendingQueueWorker = $this->getSendingQueueWorker();
  }

  private function getDirectUnsubscribeURL() {
    return SubscriptionUrlFactory::getInstance()->getUnsubscribeUrl($this->subscriber, $this->queue->id);
  }

  private function getTrackedUnsubscribeURL() {
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $links = $this->diContainer->get(Links::class);
    $data = $links->createUrlDataObject(
      $this->subscriber->getId(),
      $linkTokens->getToken($this->subscriber),
      $this->queue->id,
      $this->newsletterLink->getHash(),
      false
    );
    return Router::buildRequest(
      Track::ENDPOINT,
      Track::ACTION_CLICK,
      $data
    );
  }

  public function testItConstructs() {
    expect($this->sendingQueueWorker->mailerTask instanceof MailerTask);
    expect($this->sendingQueueWorker->newsletterTask instanceof NewsletterTask);
  }

  public function testItReturnsCorrectBatchSize(): void {
    expect($this->sendingQueueWorker->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItEnforcesExecutionLimitsBeforeQueueProcessing() {
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker(),
      [
        'processQueue' => Expected::never(),
        'enforceSendingAndExecutionLimits' => Expected::exactly(1, function() {
          throw new \Exception();
        }),
      ]);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->newslettersRepository,
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository
    );
    try {
      $sendingQueueWorker->process();
      self::fail('Execution limits function was not called.');
    } catch (\Exception $e) {
      // No exception handling needed
    }
  }

  public function testItEnforcesExecutionLimitsAfterSendingWhenQueueStatusIsNotSetToComplete() {
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker(),
      [
        'enforceSendingAndExecutionLimits' => Expected::exactly(1),
      ]);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository
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
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker(),
      [
        'enforceSendingAndExecutionLimits' => Expected::never(),
      ]);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository
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
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker(),
      [
        'processQueue' => function() {
          // this function returns a queue object
          return (object)['status' => null, 'taskId' => 0];
        },
        'enforceSendingAndExecutionLimits' => Expected::exactly(2),
      ]);
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->newslettersRepository,
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository
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
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $directUnsubscribeURL = $this->getDirectUnsubscribeURL();
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
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
        ]
      )
    );
    $sendingQueueWorker->process();
  }

  public function testItPassesExtraParametersToMailerWhenTrackingIsEnabled() {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $trackedUnsubscribeURL = $this->getTrackedUnsubscribeURL();
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
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
        ]
      )
    );
    $sendingQueueWorker->process();
  }

  public function testItCanProcessSubscribersOneByOne() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extraParams) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItSendCorrectDataToSubscribersOneByOne() {
    $subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);

    $subscriber1 = $subscribersRepository->findOneById(1);
    $subscriber1->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber1->setSource('form');
    $subscriber1->setEmail('1@localhost.com');
    $subscribersRepository->persist($subscriber1);


    $subscriber2 = $subscribersRepository->findOneById(2);
    $subscriber2->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber2->setSource('form');
    $subscriber2->setEmail('2@localhost.com');
    $subscribersRepository->persist($subscriber2);

    $subscribersRepository->flush();

    $subscribers = [
      $subscriber1,
      $subscriber2,
    ];

    $subscriberIds = array_map(
      function(SubscriberEntity $item): int {
        return (int)$item->getId();
      },
      $subscribers
    );

    $queue = SendingTask::create();
    $queue->newsletterRenderedBody = ['html' => '<p>Hello [subscriber:email]</p>', 'text' => 'Hello [subscriber:email]'];
    $queue->newsletterRenderedSubject = 'News for [subscriber:email]';
    $queue->setSubscribers($subscriberIds);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);

    $newsletter = $this->newsletter;
    $timer = 1000000000000000000;

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->make(
        MailerTask::class,
        [
          'prepareSubscriberForSending' => function($subscriber) {
            return $subscriber->get('email');
          },
          'getProcessingMethod' => 'individual',
          'send' => Expected::exactly(2, function($newsletter, $subscriberEmail, $extraParams) use ($subscribersRepository, $queue) {

            $subscriberId = explode('@', $subscriberEmail);
            $subscriberId = (int)$subscriberId[0];
            $subscriber = $subscribersRepository->findOneById($subscriberId);
            $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
            $unsubscribeUrl = $subscriptionUrlFactory->getUnsubscribeUrl($subscriber, $queue->id);
            expect($newsletter['subject'])->equals('News for ' . $subscriberEmail);
            expect($newsletter['body']['html'])->equals('<p>Hello ' . $subscriberEmail . '</p>');
            expect($newsletter['body']['text'])->equals('Hello ' . $subscriberEmail);
            expect($extraParams['meta']['email_type'])->equals('newsletter');
            expect($extraParams['meta']['subscriber_status'])->equals(SubscriberEntity::STATUS_SUBSCRIBED);
            expect($extraParams['meta']['subscriber_source'])->equals('form');
            expect($extraParams['unsubscribe_url'])->equals($unsubscribeUrl);

            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );

    $subscribersModels = [
      Subscriber::findOne($subscriber1->getId()),
      Subscriber::findOne($subscriber2->getId())
    ];

    $sendingQueueWorker->processQueue($queue, $newsletter, $subscribersModels, $timer);
  }

  public function testItCanProcessSubscribersInBulk() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
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
        ]
      )
    );
    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItProcessesStandardNewsletters() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
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
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updatedNewsletter->sentAt)->equals($updatedQueue->processedAt);

    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItHandlesSendingErrorCorrectly() {
    $wrongSubscriber = $this->createSubscriber('doe@john.com>', 'Doe', 'John');
    $queue = SendingTask::create();
    $queue->newsletterId = $this->newsletter->id;
    $queue->setSubscribers([
      $this->subscriber->getId(),
      $wrongSubscriber->getId(),
    ]);
    $queue->countTotal = 2;
    $queue->save();
    // Error that simulates sending error from the bridge
    $mailerError = new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_SOFT,
      'Error while sending.',
      null,
      [new SubscriberError($wrongSubscriber->getEmail(), 'must be an email')]
    );
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker()
    );
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => Stub::consecutive(['response' => false, 'error' => $mailerError], $this->mailerTaskDummyResponse),
        ]
      ),
      $this->subscribersRepository
    );

    $sendingQueueWorker->sendNewsletters(
      $queue,
      [$this->subscriber->getId(), $wrongSubscriber->getId()],
      [],
      [$this->subscriber->getEmail(), $wrongSubscriber->getEmail()],
      ['newsletter_id' => 1, 'subscriber_id' => 1, 'queue_id' => $queue->id],
      microtime(true)
    );

    // load queue and compare data after first sending
    $updatedQueue = SendingQueue::findOne($queue->id);
    if (!$updatedQueue instanceof SendingQueue) {
      throw new InvalidStateException();
    }
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))->equals([$this->subscriber->getId()]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))->equals([$wrongSubscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(2);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(1);

    $sendingQueueWorker->sendNewsletters(
      $queue,
      [$this->subscriber->getId()],
      [],
      [$this->subscriber->getEmail()],
      ['newsletter_id' => 1, 'subscriber_id' => 1, 'queue_id' => $queue->id],
      microtime(true)
    );

    // load queue and compare data after second sending
    $updatedQueue = SendingQueue::findOne($queue->id);
    if (!$updatedQueue instanceof SendingQueue) {
      throw new InvalidStateException();
    }
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))->equals([$this->subscriber->getId(), $wrongSubscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(2);
    expect($updatedQueue->countProcessed)->equals(2);
    expect($updatedQueue->countToProcess)->equals(0);
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();

    $this->queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $this->queue->updated_at = $originalUpdated;
    $this->queue->save();

    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->makeEmpty(MailerTask::class, [])
    );
    $sendingQueueWorker->process();

    $newQueue = ScheduledTask::findOne($this->queue->task_id);
    $this->assertInstanceOf(ScheduledTask::class, $newQueue);
    expect($newQueue->updatedAt)->notEquals($originalUpdated);
  }

  public function testItCanProcessWelcomeNewsletters() {
    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );

    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  public function testItPreventsSendingWelcomeEmailWhenSubscriberIsUnsubscribed() {
    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->subscriber->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(0),
        ]
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

  public function testItPreventsSendingNewsletterToRecipientWhoIsUnsubscribed() {
    $subscriberFactory = new \MailPoet\Test\DataFactories\Subscriber();
    $unsubscribedSubscriber = $subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $this->queue->setSubscribers([
      $this->subscriber->getId(), // subscriber that should be processed
      $unsubscribedSubscriber->getId(), // subscriber that should be skipped
    ]);
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function() {
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();

    // queue status is set to completed
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);

    // Unprocessable subscribers were removed
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([
          $this->subscriber->getId(), // subscriber that should be processed
      ]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);
  }

  public function testItRemovesNonexistentSubscribersFromProcessingList() {
    $queue = $this->queue;
    $queue->setSubscribers([
      $this->subscriber->getId(),
      12345645454,
    ]);
    $queue->countTotal = 2;
    $queue->save();
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      [
        'send' => Expected::exactly(1, function() {
          return $this->mailerTaskDummyResponse;
        }),
      ]
    );
    $sendingQueueWorker->process();

    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    expect(count($statistics))->equals(1);
  }

  public function testItDoesNotCallMailerWithEmptyBatch() {
    $queue = $this->queue;
    $subscribers = [];
    while (count($subscribers) < 2 * SendingThrottlingHandler::BATCH_SIZE) {
      $subscribers[] = 1234564545 + count($subscribers);
    }
    $subscribers[] = $this->subscriber->getId();
    $queue->setSubscribers($subscribers);
    $queue->countTotal = count($subscribers);
    $queue->save();
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      [
        'send' => Expected::exactly(1, function() {
          return $this->mailerTaskDummyResponse;
        }),
      ]
    );
    $sendingQueueWorker->process();

    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    // queue subscriber processed/to process count is updated
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals([]);
    expect($updatedQueue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals([$this->subscriber->getId()]);
    expect($updatedQueue->countTotal)->equals(1);
    expect($updatedQueue->countProcessed)->equals(1);
    expect($updatedQueue->countToProcess)->equals(0);
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
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
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
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
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
    $this->entityManager->clear();
    $this->_before();
    $subscriber = $this->subscriber;
    $subscriber->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItDoesNotSendToGloballyUnsubscribedSubscribers() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to globally unsubscribed subscriber
    $subscriber = $this->subscriber;
    $subscriber->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();
    $sendingQueueWorker->process();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    expect((int)$updatedQueue->countTotal)->equals(0);
  }

  public function testItDoesNotSendToSubscribersUnsubscribedFromSegments() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
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
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to inactive subscriber
    $subscriber = $this->subscriber;
    $subscriber->setStatus(Subscriber::STATUS_INACTIVE);
    $this->entityManager->flush();
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
    $sendingQueueWorker = $this->make(
      $this->getSendingQueueWorker()
    );
    $sendingQueueWorker->__construct(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->makeEmpty(NewslettersRepository::class),
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository
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
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::once($this->mailerTaskDummyResponse),
        ]
      )
    );
    $sendingQueueWorker->process();

    // newsletter is sent and hash remains intact
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
    $sendingQueueWorker = $this->getSendingQueueWorker();
    expect($sendingQueueWorker->getBatchSize())->equals($customBatchSizeValue);
    $wp->removeFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
  }

  public function testItReschedulesBounceTaskWhenPlannedInFarFuture() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMonths(1),
    ]);

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(MailerTask::class, [$this->diContainer->get(MailerFactory::class)], [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    $this->assertInstanceOf(ScheduledTask::class, $refetchedTask); // PHPStan
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
      $this->construct(MailerTask::class, [$this->diContainer->get(MailerFactory::class)], [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    $refetchedTask = ScheduledTask::where('id', $task->id)->findOne();
    $this->assertInstanceOf(ScheduledTask::class, $refetchedTask); // PHPStan
    expect($refetchedTask->scheduledAt)->equals($inOneHour);
  }

  public function testItPauseSendingTaskThatHasTrashedSegment() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Subject With Trashed', NewsletterEntity::STATUS_SENDING);
    $queue = $this->createQueueWithTaskAndSegment($newsletter, null, ['html' => 'Hello', 'text' => 'Hello']);
    $segment = $this->createSegment('Segment test', SegmentEntity::TYPE_DEFAULT);
    $segment->setDeletedAt(new \DateTime());
    $this->entityManager->flush();
    $this->addSegmentToNewsletter($newsletter, $segment);

    $sendingQueueWorker = $this->getSendingQueueWorker();
    $sendingQueueWorker->process();

    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->entityManager->refresh($task);
    $this->entityManager->refresh($newsletter);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    expect($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    expect($this->wp->getTransient(SendingQueueWorker::EMAIL_WITH_INVALID_SEGMENT_OPTION))->equals('Subject With Trashed');
  }

  public function testItPauseSendingTaskThatHasDeletedSegment() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Subject With Deleted', NewsletterEntity::STATUS_SENDING);
    $queue = $this->createQueueWithTaskAndSegment($newsletter, null, ['html' => 'Hello', 'text' => 'Hello']);
    $segment = $this->createSegment('Segment test', SegmentEntity::TYPE_DEFAULT);
    $this->addSegmentToNewsletter($newsletter, $segment);
    $this->entityManager->createQueryBuilder()->delete(SegmentEntity::class, 's')
      ->where('s.id = :id')
      ->setParameter('id', $segment->getId())
      ->getQuery()
      ->execute();
    $sendingQueueWorker = $this->getSendingQueueWorker();
    $sendingQueueWorker->process();

    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->entityManager->refresh($task);
    $this->entityManager->refresh($newsletter);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    expect($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    expect($this->wp->getTransient(SendingQueueWorker::EMAIL_WITH_INVALID_SEGMENT_OPTION))->equals('Subject With Deleted');
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(NewsletterPostEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }

  private function createNewsletter(string $type, $subject, string $status = NewsletterEntity::STATUS_DRAFT): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject($subject);
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createSubscriber(string $email, string $firstName, string $lastName): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName($firstName);
    $subscriber->setLastName($lastName);
    $subscriber->setStatus(Subscriber::STATUS_SUBSCRIBED);
    $subscriber->setSource('administrator');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSegment(string $name, string $type): SegmentEntity {
    $segment = new SegmentEntity($name, $type, 'Description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function addSegmentToNewsletter(NewsletterEntity $newsletter, SegmentEntity $segment) {
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $newsletter->getNewsletterSegments()->add($newsletterSegment);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();
  }

  private function createQueueWithTaskAndSegment(NewsletterEntity $newsletter, $status = null, $body = null): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus($status);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    if ($body) {
      $queue->setNewsletterRenderedBody($body);
    }
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $this->entityManager->flush();
    return $queue;
  }

  private function getSendingQueueWorker($mailerMock = null): SendingQueueWorker {
    return new SendingQueueWorker(
      $this->sendingErrorHandler,
      $this->sendingThrottlingHandler,
      $this->statsNotificationsWorker,
      $this->loggerFactory,
      $this->newslettersRepository,
      $this->cronHelper,
      $this->subscribersFinder,
      $this->segmentsRepository,
      $this->wp,
      $this->tasksLinks,
      $this->scheduledTasksRepository,
      $mailerMock ?? $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository
    );
  }
}
