<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\Config\Populator;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Bounce;
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
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingQueueTest extends \MailPoetTest {
  /** @var SendingQueueWorker */
  public $sendingQueueWorker;
  public $cronHelper;
  public $newsletterLink;
  /* @var SendingQueueEntity */
  public $sendingQueue;
  public $newsletterSegment;
  public $newsletter;
  public $subscriberSegment;
  /** @var SegmentEntity */
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

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var ScheduledTaskEntity */
  private $scheduledTask;

  /** NewsletterEntity */
  private $newsletterEntity;

  public function _before() {
    parent::_before();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->segment = (new SegmentFactory())->withName('segment')->create();
    $this->subscriber = $this->createSubscriber('john@doe.com', 'John', 'Doe', [$this->segment]);

    /** @var Newsletter $newsletter */
    $newsletter = Newsletter::create();
    $this->newsletter = $newsletter;
    $this->newsletter->type = NewsletterEntity::TYPE_STANDARD;
    $this->newsletter->status = NewsletterEntity::STATUS_ACTIVE;

    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->save();

    $this->newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $this->newsletterEntity);

    /** @var NewsletterSegment $newsletterSegment */
    $newsletterSegment = NewsletterSegment::create();
    $this->newsletterSegment = $newsletterSegment;
    $this->newsletterSegment->newsletterId = $this->newsletter->id;
    $this->newsletterSegment->segmentId = (int)$this->segment->getId();
    $this->newsletterSegment->save();

    $this->sendingQueue = $this->createQueueWithTask($this->newsletterEntity);
    $scheduledTask = $this->sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->scheduledTask = $scheduledTask;
    $this->scheduledTaskSubscribersRepository->setSubscribers($this->scheduledTask, [$this->subscriber->getId()]);

    $queue = $this->newsletterEntity->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->newsletterLink = new NewsletterLinkEntity(
      $this->newsletterEntity,
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
    $this->tasksLinks = $this->diContainer->get(TasksLinks::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->sendingQueueWorker = $this->getSendingQueueWorker();
  }

  private function getDirectUnsubscribeURL() {
    return SubscriptionUrlFactory::getInstance()->getUnsubscribeUrl($this->subscriber, (int)$this->sendingQueue->getId());
  }

  private function getTrackedUnsubscribeURL() {
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $links = $this->diContainer->get(Links::class);
    $data = $links->createUrlDataObject(
      $this->subscriber->getId(),
      $linkTokens->getToken($this->subscriber),
      $this->sendingQueue->getId(),
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
    verify($this->sendingQueueWorker->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
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
      $this->scheduledTaskSubscribersRepository,
      $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
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
      $this->scheduledTaskSubscribersRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );
    $sendingQueueWorker->sendNewsletters(
      $this->scheduledTask,
      $preparedSubscribers = [],
      $preparedNewsletters = [],
      $preparedSubscribers = [],
      $statistics[] = [
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $this->sendingQueue->getId(),
      ],
      microtime(true)
    );
  }

  public function testItDoesNotEnforceExecutionLimitsAfterSendingWhenQueueStatusIsSetToComplete() {
    // when sending is done and there are no more subscribers to process, continue
    // without enforcing execution limits. this allows the newsletter to be marked as sent
    // in the process() method and after that execution limits will be enforced
    $this->scheduledTask->setStatus(SendingQueue::STATUS_COMPLETED);
    $this->entityManager->persist($this->scheduledTask);
    $this->entityManager->flush();
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
      $this->scheduledTaskSubscribersRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );
    $sendingQueueWorker->sendNewsletters(
      $this->scheduledTask,
      $preparedSubscribers = [],
      $preparedNewsletters = [],
      $preparedSubscribers = [],
      $statistics[] = [
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $this->sendingQueue->getId(),
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
      $this->scheduledTaskSubscribersRepository,
      $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );
    $sendingQueueWorker->process();
  }

  public function testItDeletesQueueWhenNewsletterIsNotFound() {
    // queue exists
    $queue = SendingQueue::findOne($this->sendingQueue->getId());
    verify($queue)->notEquals(false);

    // delete newsletter
    $this->newslettersRepository->bulkDelete([$this->newsletter->id]);

    // queue no longer exists
    $this->sendingQueueWorker->process();
    $queue = SendingQueue::findOne($this->sendingQueue->getId());
    verify($queue)->false();
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
            verify(isset($extraParams['unsubscribe_url']))->true();
            verify($extraParams['unsubscribe_url'])->equals($directUnsubscribeURL);
            verify(isset($extraParams['meta']))->true();
            verify($extraParams['meta']['email_type'])->equals('newsletter');
            verify($extraParams['meta']['subscriber_status'])->equals('subscribed');
            verify($extraParams['meta']['subscriber_source'])->equals('administrator');
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
            verify(isset($extraParams['unsubscribe_url']))->true();
            verify($extraParams['unsubscribe_url'])->equals($trackedUnsubscribeURL);
            verify(isset($extraParams['meta']))->true();
            verify($extraParams['meta']['email_type'])->equals('newsletter');
            verify($extraParams['meta']['subscriber_status'])->equals('subscribed');
            verify($extraParams['meta']['subscriber_source'])->equals('administrator');
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
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $this->subscriber->setEngagementScoreUpdatedAt(Carbon::now()->subDays(5));
    $this->entityManager->flush();
    $this->entityManager->refresh($this->subscriber);
    verify($this->subscriber->getLastSendingAt())->null();
    verify($this->subscriber->getEngagementScoreUpdatedAt())->notNull();
    $sendingQueueWorker->process();
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getLastSendingAt())->notNull();
    verify($this->subscriber->getEngagementScoreUpdatedAt())->null();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
    verify($updatedNewsletter->status)->equals(NewsletterEntity::STATUS_SENT);

    // queue status is set to completed
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getStatus())->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->arrayCount(0);
    $processedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($processedSubscribers)->equals([$this->subscriber]);
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->sendingQueue->getId())
      ->findOne();
    verify($statistics)->notEquals(false);
  }

  public function testItSendCorrectDataToSubscribersOneByOne() {
    $subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);

    $subscriber1 = $this->createSubscriber('1@localhost.com', 'firstName', 'lastName');
    $subscriber1->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber1->setSource('form');
    $subscriber1->setEmail('1@localhost.com');
    $subscribersRepository->persist($subscriber1);

    $subscriber2 = $this->createSubscriber('2@lcoalhost.com', 'first', 'last');
    $subscriber2->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber2->setSource('form');
    $subscriber2->setEmail('2@localhost.com');
    $subscribersRepository->persist($subscriber2);

    $sendingQueue = $this->createQueueWithTask($this->newsletterEntity);

    $sendingQueue->setNewsletterRenderedBody(['html' => '<p>Hello [subscriber:email]</p>', 'text' => 'Hello [subscriber:email]']);
    $sendingQueue->setNewsletterRenderedSubject('News for [subscriber:email]');
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->scheduledTaskSubscribersRepository->setSubscribers($scheduledTask, [$subscriber1->getId(), $subscriber2->getId()]);

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
          'send' => Expected::exactly(2, function($newsletter, $subscriberEmail, $extraParams) use ($subscribersRepository, $sendingQueue) {

            $subscriber = $subscribersRepository->findOneBy(['email' => $subscriberEmail]);
            $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
            $unsubscribeUrl = $subscriptionUrlFactory->getUnsubscribeUrl($subscriber, (int)$sendingQueue->getId());
            verify($newsletter['subject'])->equals('News for ' . $subscriberEmail);
            verify($newsletter['body']['html'])->equals('<p>Hello ' . $subscriberEmail . '</p>');
            verify($newsletter['body']['text'])->equals('Hello ' . $subscriberEmail);
            verify($extraParams['meta']['email_type'])->equals('newsletter');
            verify($extraParams['meta']['subscriber_status'])->equals(SubscriberEntity::STATUS_SUBSCRIBED);
            verify($extraParams['meta']['subscriber_source'])->equals('form');
            verify($extraParams['unsubscribe_url'])->equals($unsubscribeUrl);

            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );

    $subscribersModels = [
      Subscriber::findOne($subscriber1->getId()),
      Subscriber::findOne($subscriber2->getId()),
    ];

    $sendingQueueWorker->processQueue($scheduledTask, $newsletter, $subscribersModels, $timer);
  }

  public function testItCanProcessSubscribersInBulk() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'sendBulk' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            verify(!empty($newsletter[0]['body']['html']))->true();
            verify(!empty($newsletter[0]['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
          'getProcessingMethod' => Expected::exactly(1, function() {
            return 'bulk';
          }),
        ]
      )
    );
    verify($this->subscriber->getLastSendingAt())->null();
    $sendingQueueWorker->process();
    $this->subscribersRepository->refresh($this->subscriber);
    verify($this->subscriber->getLastSendingAt())->notNull();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
    verify($updatedNewsletter->status)->equals(NewsletterEntity::STATUS_SENT);

    // queue status is set to completed
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getStatus())->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->arrayCount(0);
    $processedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($processedSubscribers)->equals([$this->subscriber]);
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->sendingQueue->getId())
      ->findOne();
    verify($statistics)->notEquals(false);
  }

  public function testItProcessesStandardNewsletters() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();

    // queue status is set to completed
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getStatus())->equals(SendingQueue::STATUS_COMPLETED);

    // newsletter status is set to sent and sent_at date is populated
    $updatedNewsletter = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    verify($updatedNewsletter->getSentAt())->equalsWithDelta($scheduledTask->getProcessedAt(), 1);

    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->arrayCount(0);
    $processedSubscribers = $scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($processedSubscribers)->equals([$this->subscriber]);
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->sendingQueue->getId())
      ->findOne();
    verify($statistics)->notEquals(false);
  }

  public function testItHandlesSendingErrorCorrectly() {
    $wrongSubscriber = $this->createSubscriber('doe@john.com>', 'Doe', 'John');

    $sendingQueue = $this->createQueueWithTask($this->newsletterEntity);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->scheduledTaskSubscribersRepository->setSubscribers($scheduledTask, [$this->subscriber->getId(), $wrongSubscriber->getId()]);

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
      $this->scheduledTaskSubscribersRepository,
      $this->make(
        new MailerTask($this->diContainer->get(MailerFactory::class)),
        [
          'sendBulk' => Stub::consecutive(['response' => false, 'error' => $mailerError], $this->mailerTaskDummyResponse),
        ]
      ),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );

    $sendingQueueWorker->sendNewsletters(
      $scheduledTask,
      [$this->subscriber->getId(), $wrongSubscriber->getId()],
      [],
      [$this->subscriber->getEmail(), $wrongSubscriber->getEmail()],
      ['newsletter_id' => 1, 'subscriber_id' => 1, 'queue_id' => $sendingQueue->getId()],
      microtime(true)
    );

    // compare data after first sending
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))->equals([$this->subscriber]);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))->equals([$wrongSubscriber]);
    verify($sendingQueue->getCountTotal())->equals(2);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(1);

    $sendingQueueWorker->sendNewsletters(
      $scheduledTask,
      [$this->subscriber->getId()],
      [],
      [$this->subscriber->getEmail()],
      ['newsletter_id' => 1, 'subscriber_id' => 1, 'queue_id' => $sendingQueue->getId()],
      microtime(true)
    );

    // load queue and compare data after second sending
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))->equals([]);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))->equals([$this->subscriber, $wrongSubscriber]);
    verify($sendingQueue->getCountTotal())->equals(2);
    verify($sendingQueue->getCountProcessed())->equals(2);
    verify($sendingQueue->getCountToProcess())->equals(0);
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5);
    $this->setUpdatedAtForEntity($this->scheduledTask, $originalUpdated);

    $this->newsletter->type = NewsletterEntity::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->makeEmpty(MailerTask::class, [])
    );
    $sendingQueueWorker->process();

    verify($this->scheduledTask->getUpdatedAt())->notEquals($originalUpdated);
  }

  public function testItCanProcessWelcomeNewsletters() {
    $this->newsletter->type = NewsletterEntity::TYPE_WELCOME;
    $this->newsletterSegment->delete();

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );

    $sendingQueueWorker->process();

    // newsletter status is set to sent
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
    verify($updatedNewsletter->status)->equals(NewsletterEntity::STATUS_SENT);

    // queue status is set to completed
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    verify($scheduledTask->getStatus())->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->equals([]);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([$this->subscriber]);
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->getId())
      ->where('queue_id', $this->sendingQueue->getId())
      ->findOne();
    verify($statistics)->notEquals(false);
  }

  public function testItPreventsSendingWelcomeEmailWhenSubscriberIsUnsubscribed() {
    $this->newsletter->type = NewsletterEntity::TYPE_WELCOME;
    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
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
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);

    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([]);
    verify($sendingQueue->getCountTotal())->equals(0);
    verify($sendingQueue->getCountProcessed())->equals(0);
    verify($sendingQueue->getCountToProcess())->equals(0);
  }

  public function testItPreventsSendingNewsletterToRecipientWhoIsUnsubscribed() {
    $subscriberFactory = new \MailPoet\Test\DataFactories\Subscriber();
    $unsubscribedSubscriber = $subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    $this->scheduledTaskSubscribersRepository->setSubscribers(
      $this->scheduledTask,
      [$this->subscriber->getId(), $unsubscribedSubscriber->getId()]
    );

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
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);

    // Unprocessable subscribers were removed
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([$this->subscriber]); // subscriber that should be processed
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);
  }

  public function testItRemovesSubscribersFromProcessingListWhenNewsletterHasSegmentAndSubscriberIsNotPartOfIt() {
    $subscriberNotPartOfNewsletterSegment = $this->createSubscriber('subscriber1@mailpoet.com', 'Subscriber', 'One');

    $this->scheduledTaskSubscribersRepository->setSubscribers(
      $this->scheduledTask,
      [$this->subscriber->getId(), $subscriberNotPartOfNewsletterSegment->getId()]
    );

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

    // queue subscriber processed/to process count is updated
    verify($this->scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->equals([]);
    verify($this->scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([$this->subscriber]);
    verify($this->sendingQueue->getCountTotal())->equals(1);
    verify($this->sendingQueue->getCountProcessed())->equals(1);
    verify($this->sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    verify(count($statistics))->equals(1);
  }

  public function testItRemovesSubscribersFromProcessingListWhenNewsletterHasNoSegment() {
    $this->newsletterEntity->getNewsletterSegments()->clear();
    $invalidSubscriberId = 99999;

    $this->scheduledTaskSubscribersRepository->setSubscribers(
      $this->scheduledTask,
      [$this->subscriber->getId(), $invalidSubscriberId]
    );

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

    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($this->sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($this->sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->equals([]);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([$this->subscriber]);
    verify($this->sendingQueue->getCountTotal())->equals(1);
    verify($this->sendingQueue->getCountProcessed())->equals(1);
    verify($this->sendingQueue->getCountToProcess())->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    verify(count($statistics))->equals(1);
  }

  public function testItDoesNotCallMailerWithEmptyBatch() {
    $subscribers = [];
    while (count($subscribers) < 2 * SendingThrottlingHandler::BATCH_SIZE) {
      $subscribers[] = 1234564545 + count($subscribers);
    }
    $subscribers[] = $this->subscriber->getId();

    $this->scheduledTaskSubscribersRepository->setSubscribers($this->scheduledTask, $subscribers);
    $this->sendingQueue->setCountTotal(count($subscribers));
    $this->entityManager->persist($this->sendingQueue);
    $this->entityManager->flush();

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

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $this->scheduledTasksRepository->findOneBySendingQueue($sendingQueue);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    $this->scheduledTasksRepository->refresh($scheduledTask);
    // queue subscriber processed/to process count is updated
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->equals([]);
    verify($scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([$this->subscriber]);
    verify($sendingQueue->getCountTotal())->equals(1);
    verify($sendingQueue->getCountProcessed())->equals(1);
    verify($sendingQueue->getCountToProcess())->equals(0);
  }

  public function testItUpdatesQueueSubscriberCountWhenNoneOfSubscribersExist() {
    $this->scheduledTaskSubscribersRepository->setSubscribers($this->scheduledTask, [123, 456,]);
    $this->sendingQueue->setCountTotal(2);
    $this->entityManager->persist($this->sendingQueue);
    $this->entityManager->flush();

    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );
    $sendingQueueWorker->process();

    // queue subscriber processed/to process count is updated
    verify($this->scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED))
      ->equals([]);
    verify($this->scheduledTask->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED))
      ->equals([]);
    verify($this->sendingQueue->getCountTotal())->equals(0);
    verify($this->sendingQueue->getCountProcessed())->equals(0);
    verify($this->sendingQueue->getCountToProcess())->equals(0);
  }

  public function testItDoesNotSendToTrashedSubscribers() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to trashed subscriber
    $subscriber = $this->subscriber;
    $subscriber->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    $sendingQueueWorker->process();

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    verify($sendingQueue->getCountTotal())->equals(0);
  }

  /**
   * @dataProvider dataForTestItSendsTransactionalEmails
   */
  public function testItSendsTransactionalEmails(string $subscriberStatus, bool $expectSending) {

    $this->newsletter->type = NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL;
    $this->newsletter->save();
    $this->newsletterSegment->delete();
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );

    $subscriber = $this->subscriber;
    $subscriber->setStatus($subscriberStatus);
    $this->entityManager->flush();
    $sendingQueueWorker->process();

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    verify($sendingQueue->getCountTotal())->equals($expectSending ? 1 : 0);
    // Transactional emails shouldn't update last sending at
    $this->subscribersRepository->refresh($subscriber);
    verify($subscriber->getLastSendingAt())->null();
  }

  public function dataForTestItSendsTransactionalEmails(): array {
    return [
      SubscriberEntity::STATUS_UNCONFIRMED => [SubscriberEntity::STATUS_UNCONFIRMED, true],
      SubscriberEntity::STATUS_SUBSCRIBED => [SubscriberEntity::STATUS_SUBSCRIBED, true],
      SubscriberEntity::STATUS_UNSUBSCRIBED => [SubscriberEntity::STATUS_UNSUBSCRIBED, true],
      SubscriberEntity::STATUS_BOUNCED => [SubscriberEntity::STATUS_BOUNCED, false],
      SubscriberEntity::STATUS_INACTIVE => [SubscriberEntity::STATUS_INACTIVE, true],
    ];
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
    $subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();
    $sendingQueueWorker->process();

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    verify($sendingQueue->getCountTotal())->equals(0);
  }

  public function testItDoesNotSendToSubscribersUnsubscribedFromSegments() {
    $sendingQueueWorker = $this->sendingQueueWorker;
    $sendingQueueWorker->mailerTask = $this->construct(
      MailerTask::class,
      [$this->diContainer->get(MailerFactory::class)],
      ['send' => $this->mailerTaskDummyResponse]
    );

    // newsletter is not sent to subscriber unsubscribed from segment
    $subscriberSegment = $this->subscriber->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);

    $sendingQueueWorker->process();

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    verify($sendingQueue->getCountTotal())->equals(0);
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
    $subscriber->setStatus(SubscriberEntity::STATUS_INACTIVE);
    $this->entityManager->flush();
    $sendingQueueWorker->process();

    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingQueue->getId());
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $this->sendingQueuesRepository->refresh($sendingQueue);
    verify($sendingQueue->getCountTotal())->equals(0);
  }

  public function testItPausesSendingWhenProcessedSubscriberListCannotBeUpdated() {
    $scheduledTaskSubscribersRepository = $this->createMock(ScheduledTaskSubscribersRepository::class);
    $scheduledTaskSubscribersRepository
      ->method('updateProcessedSubscribers')
      ->willThrowException(new \Exception());
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
      $scheduledTaskSubscribersRepository,
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'sendBulk' => $this->mailerTaskDummyResponse,
        ]
      ),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );
    try {
      $sendingQueueWorker->sendNewsletters(
        $this->scheduledTask,
        $preparedSubscribers = [],
        $preparedNewsletters = [],
        $preparedSubscribers = [],
        $statistics = [],
        microtime(true)
      );
      $this->fail('Paused sending exception was not thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals('Sending has been paused.');
    }
    $mailerLog = MailerLog::getMailerLog();
    verify($mailerLog['status'])->equals(MailerLog::STATUS_PAUSED);
    verify($mailerLog['error'])->equals(
      [
        'operation' => 'processed_list_update',
        'error_message' => "QUEUE-{$this->sendingQueue->getId()}-PROCESSED-LIST-UPDATE",
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
    verify($updatedNewsletter->status)->equals(NewsletterEntity::STATUS_SENT);
    verify($updatedNewsletter->hash)->equals($this->newsletter->hash);
  }

  public function testItAllowsSettingCustomBatchSize() {
    $customBatchSizeValue = 10;
    $filter = function() use ($customBatchSizeValue) {
      return $customBatchSizeValue;
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
    $sendingQueueWorker = $this->getSendingQueueWorker();
    verify($sendingQueueWorker->getBatchSize())->equals($customBatchSizeValue);
    $wp->removeFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
  }

  public function testItReschedulesBounceTaskWhenPlannedInFarFuture() {
    $task = (new ScheduledTaskFactory())->create(
      Bounce::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMonths(1)
    );

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(MailerTask::class, [$this->diContainer->get(MailerFactory::class)], [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    verify($task->getScheduledAt())->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(42));
  }

  public function testDoesNotRescheduleBounceTaskWhenPlannedInNearFuture() {
    $inOneHour = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1);
    $task = (new ScheduledTaskFactory())->create(
      Bounce::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      $inOneHour
    );

    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(MailerTask::class, [$this->diContainer->get(MailerFactory::class)], [
        'send' => $this->mailerTaskDummyResponse,
      ])
    );
    $sendingQueueWorker->process();

    verify($task->getScheduledAt())->equals($inOneHour);
  }

  public function testItPauseSendingTaskThatHasTrashedSegment() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Subject With Trashed', NewsletterEntity::STATUS_SENDING);
    $queue = $this->createQueueWithTask($newsletter, null, ['html' => 'Hello', 'text' => 'Hello']);
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
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    verify($this->wp->getTransient(SendingQueueWorker::EMAIL_WITH_INVALID_SEGMENT_OPTION))->equals('Subject With Trashed');
  }

  public function testItPauseSendingTaskThatHasDeletedSegment() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Subject With Deleted', NewsletterEntity::STATUS_SENDING);
    $queue = $this->createQueueWithTask($newsletter, null, ['html' => 'Hello', 'text' => 'Hello']);
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
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
    verify($this->wp->getTransient(SendingQueueWorker::EMAIL_WITH_INVALID_SEGMENT_OPTION))->equals('Subject With Deleted');
  }

  public function testItGeneratesPartOfAnMD5CampaignIdStoredAsSendingQueueMeta() {
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();
    $meta = $this->sendingQueue->getMeta();
    verify(isset($meta['campaignId']))->true();
    $campaignId = $meta['campaignId'];
    verify(strlen($campaignId))->equals(16);
  }

  public function testItPassesCampaignIdToMailerViaExtraParamsMeta() {
    $mailerTaskExtraParams = [];
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extraParams = []) use (&$mailerTaskExtraParams) {
            // newsletter body should not be empty
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            $mailerTaskExtraParams = $extraParams;
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();
    $meta = $this->sendingQueue->getMeta();
    verify(isset($meta['campaignId']))->true();
    $campaignId = $meta['campaignId'];
    verify($mailerTaskExtraParams['meta']['campaign_id'])->equals($campaignId);
  }

  public function testCampaignIdsAreTheSameForDifferentSubscribers() {
    $mailerTaskCampaignIds = [];
    $secondSubscriber = $this->createSubscriber('sub2@example.com', 'Subscriber', 'Two', [$this->segment]);
    $this->scheduledTaskSubscribersRepository->setSubscribers(
      $this->scheduledTask,
      [$this->subscriber->getId(), $secondSubscriber->getId()]
    );
    $sendingQueueWorker = $this->getSendingQueueWorker(
      $this->construct(
        MailerTask::class,
        [$this->diContainer->get(MailerFactory::class)],
        [
          'send' => Expected::exactly(2, function($newsletter, $subscriber, $extraParams = []) use (&$mailerTaskCampaignIds) {
            // newsletter body should not be empty
            verify(!empty($newsletter['body']['html']))->true();
            verify(!empty($newsletter['body']['text']))->true();
            $mailerTaskCampaignIds[$subscriber] = $extraParams['meta']['campaign_id'];
            return $this->mailerTaskDummyResponse;
          }),
        ]
      )
    );
    $sendingQueueWorker->process();
    $meta = $this->sendingQueue->getMeta();
    verify(isset($meta['campaignId']))->true();
    $campaignId = $meta['campaignId'];
    verify(count($mailerTaskCampaignIds))->equals(2);
    foreach (array_values($mailerTaskCampaignIds) as $mailerTaskCampaignId) {
      verify($mailerTaskCampaignId)->equals($campaignId);
    }
  }

  public function testSendingGetsStuckWhenSubscribersAreUnsubscribed() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, 'Subject With Deleted', NewsletterEntity::STATUS_SENDING);
    [$segment, $subscriber] = $this->createListWithSubscriber();
    $this->addSegmentToNewsletter($newsletter, $segment);
    $queue = $this->createQueueWithTask($newsletter, null, ['html' => 'Hello', 'text' => 'Hello']);
    $subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $sendingQueueWorker = $this->getSendingQueueWorker();
    $sendingQueueWorker->process();

    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_INVALID);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testProcessMarksScheduledTaskInProgressAsFalseWhenProperlyProcessingTask() {
    $sendingQueueWorker = $this->getSendingQueueWorker();
    $sendingQueueWorker->process();
    $this->assertSame(false, $this->scheduledTask->getInProgress());
  }

  public function testProcessMarksScheduledTaskProgressAsFinishedWhenThereIsAnErrorProcessingTask() {
    $mailerTask = $this->createMock(MailerTask::class);
    $mailerTask
      ->method('send')
      ->willThrowException(new \Exception());
    $mailerTask
      ->method('getProcessingMethod')
      ->willReturn('individual');
    $sendingQueueWorker = $this->getSendingQueueWorker($mailerTask);

    try {
      $sendingQueueWorker->process();
    } catch (\Exception $e) {
      // do nothing
    }

    $this->assertSame(false, $this->scheduledTask->getInProgress());
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

  private function createSubscriber(string $email, string $firstName, string $lastName, $segments = []): SubscriberEntity {
    $subscriber = (new SubscriberFactory())
      ->withEmail($email)
      ->withFirstName($firstName)
      ->withLastName($lastName)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSource(Source::ADMINISTRATOR)
      ->withSegments($segments)
      ->create();

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

  private function createQueueWithTask(NewsletterEntity $newsletter, $status = null, $body = null): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueueWorker::TASK_TYPE);
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
    $this->entityManager->refresh($queue); // I'm not sure why calling refresh() here is needed and why the tests fail without it (calling $task->getSendingQueue() returns null)
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
      $this->scheduledTaskSubscribersRepository,
      $mailerMock ?? $this->diContainer->get(MailerTask::class),
      $this->subscribersRepository,
      $this->sendingQueuesRepository,
      $this->entityManager
    );
  }

  private function createListWithSubscriber(): array {
    $segmentFactory = new SegmentFactory();
    $segmentName = 'List ' . Security::generateRandomString();
    $segment = $segmentFactory->withName($segmentName)->create();

    $subscriberFactory = new SubscriberFactory();
    $subscriberEmail = Security::generateRandomString() . '@domain.com';
    $subscriberFirstName = 'John';
    $subscriberLastName = 'Doe';
    $subscriber = $subscriberFactory->withSegments([$segment])
      ->withEmail($subscriberEmail)
      ->withFirstName($subscriberFirstName)
      ->withLastName($subscriberLastName)
      ->create();
    return [$segment, $subscriber];
  }
}
