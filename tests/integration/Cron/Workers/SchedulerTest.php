<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SchedulerTest extends \MailPoetTest {
  public $cron_helper;
  /** @var LoggerFactory */
  private $logger_factory;

  public function _before() {
    parent::_before();
    $this->logger_factory = LoggerFactory::getInstance();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
  }

  public function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
      $scheduler->process(microtime(true) - $this->cron_helper->getDaemonExecutionLimit());
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  public function testItCanGetScheduledQueues() {
    expect(Scheduler::getScheduledQueues())->isEmpty();
    $queue = SendingTask::create();
    $queue->newsletter_id = 1;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    expect(Scheduler::getScheduledQueues())->notEmpty();
  }

  public function testItCanCreateNotificationHistory() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $newsletter->save();

    // ensure that notification history does not exist
    $notification_history = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notification_history)->isEmpty();

    // create notification history and ensure that it exists
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->createNotificationHistory($newsletter->id);
    $notification_history = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notification_history)->notEmpty();
  }

  public function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, 'immediately');
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // queue and associated newsletter should be deleted when interval type is set to "immediately"
    expect(SendingQueue::findMany())->notEmpty();
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, 'daily');
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // queue's next run date should change when interval type is set to anything
    // other than "immediately"
    $queue = $this->_createQueue($newsletter->id);
    $newsletter_option->value = 'daily';
    $newsletter_option->save();
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)->findOne($newsletter->id);
    expect($queue->scheduled_at)->null();
    $newsletter->schedule = '0 5 * * *'; // set it to daily at 5
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    $queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($queue->scheduled_at)->notNull();
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberIsNotAWPUser() {
    $WP_user = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber();
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field = $this->_createNewsletterOptionField(
      'role',
      Newsletter::TYPE_WELCOME
    );
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
    $WP_user = $this->_createOrUpdateWPUser('editor');
    $subscriber = $this->_createSubscriber($WP_user->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field = $this->_createNewsletterOptionField(
      'role',
      Newsletter::TYPE_WELCOME
    );
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return false and delete queue when subscriber role is different from the one
    // specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(count(SendingQueue::findMany()))->equals(0);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberExistsAndRoleMatches() {
    $WP_user = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($WP_user->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  public function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $WP_user = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($WP_user->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      WelcomeScheduler::WORDPRESS_ALL_ROLES
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // true when user exists and has any role
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenThereAreNoSubscribersToProcess() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([]);

    // delete queue when the list of subscribers to process is blank
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $result = $scheduler->processWelcomeNewsletter($newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenWPUserCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);

    // return false when WP user cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItDoesNotProcessWelcomeNewsletterWhenSubscriberCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);

    // return false when subscriber cannot be verified
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1, function() {
        return false;
      }),
    ], $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  public function testItProcessesWelcomeNewsletterWhenSubscriberIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';

    // return true when subsriber is verified and update the queue's status to null
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyMailpoetSubscriber' => Expected::exactly(1),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($updated_queue->status)->null();
  }

  public function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';

    // return true when WP user is verified
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers([1]);
    $scheduler = Stub::make(Scheduler::class, [
      'verifyWPSubscriber' => Expected::exactly(1),
    ], $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    // update queue's status to null
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($updated_queue->status)->null();
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber(null, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber can't be found
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItFailsMailpoetSubscriberVerificationWhenSubscriberIsNotInSegment() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field = $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber is not in segment specified for the newsletter
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItReschedulesQueueDeliveryWhenMailpoetSubscriberHasNotConfirmedSubscription() {
    $current_time = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $subscriber = $this->_createSubscriber($wp_user_id = null, 'unconfirmed');
    $segment = $this->_createSegment();
    $subscriber_segment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect(Carbon::parse($updated_queue->scheduled_at))->equals(
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
        ->addMinutes(ScheduledTask::BASIC_RESCHEDULE_TIMEOUT)
    );
  }

  public function testItDoesntRunQueueDeliveryWhenMailpoetSubscriberHasUnsubscribed() {
    $current_time = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    Carbon::setTestNow($current_time); // mock carbon to return current time
    $subscriber = $this->_createSubscriber($wp_user_id = null, 'unsubscribed');
    $segment = $this->_createSegment();
    $subscriber_segment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    expect(SendingQueue::findOne($queue->id))->false();
  }

  public function testItCanVerifyMailpoetSubscriber() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $subscriber_segment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);

    // return true after successful verification
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
  }

  public function testItProcessesScheduledStandardNewsletter() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $subscriber_segment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter = $this->_createNewsletter();
    $newsletter_segment = $this->_createNewsletterSegment($newsletter->id, $segment->id);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler(new SubscribersFinder(), $this->logger_factory, $this->cron_helper);

    // return true
    expect($scheduler->processScheduledStandardNewsletter($newsletter, $queue))->true();
    // update queue's list of subscribers to process
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    $updated_queue_subscribers = $updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($updated_queue_subscribers)->equals([$subscriber->id]);
    // set queue's status to null
    expect($updated_queue->status)->null();
    // set newsletter's status to sending
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENDING);
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSegmentsDontExist() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // delete or reschedule queue when segments don't exist
    $scheduler = Stub::make(Scheduler::class, [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
      'logger_factory' => $this->logger_factory,
      'cron_helper' => $this->cron_helper,
    ], $this);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletter_segment = $this->_createNewsletterSegment($newsletter->id, $segment->id);

    // delete or reschedule queue when there are no subscribers in segments
    $scheduler = $this->construct(Scheduler::class, [new SubscribersFinder(), $this->logger_factory, $this->cron_helper], [
      'deleteQueueOrUpdateNextRunDate' => Expected::exactly(1, function() {
        return false;
      }),
    ]);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  public function testItCanProcessPostNotificationNewsletter() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletter_segment = $this->_createNewsletterSegment($newsletter->id, $segment->id);
    $subscriber = $this->_createSubscriber();
    $subscriber_segment = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      $segment->id
    );
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($newsletter->id);
    $scheduler = new Scheduler(new SubscribersFinder(), $this->logger_factory, $this->cron_helper);

    // return true
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->true();
    // create notification history
    $notification_history = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    expect($notification_history)->notEmpty();
    // update queue with a list of subscribers to process and change newsletter id
    // to that of the notification history
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    $updated_queue_subscribers = $updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($updated_queue_subscribers)->equals([$subscriber->id]);
    expect($updated_queue->newsletter_id)->equals($notification_history->id);
    // set notification history's status to sending
    $updated_notification_history = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    expect($updated_notification_history->status)->equals(Newsletter::STATUS_SENDING);
  }

  public function testItFailsToProcessWhenScheduledQueuesNotFound() {
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    expect($scheduler->process())->false();
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $queue = $this->_createQueue(1);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItDeletesQueueDuringProcessingWhenNewsletterIsSoftDeleted() {
    $newsletter = $this->_createNewsletter();
    $newsletter->deleted_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $newsletter->save();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItProcessesWelcomeNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processWelcomeNewsletter' => Expected::exactly(1),
      'cron_helper' => $this->cron_helper,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesNotificationNewsletters() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cron_helper' => $this->cron_helper,
    ], $this);
    $scheduler->process();
  }

  public function testItProcessesStandardScheduledNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::exactly(1),
      'cron_helper' => $this->cron_helper,
    ], $this);
    $scheduler->process();
  }

  public function testItEnforcesExecutionLimitDuringProcessing() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();
    $scheduler = Stub::make(Scheduler::class, [
      'processPostNotificationNewsletter' => Expected::exactly(1),
      'cron_helper' => $this->make(CronHelper::class, [
        'enforceExecutionLimit' => Expected::exactly(2), // call at start + during processing
      ]),
    ], $this);
    $scheduler->process();
  }

  public function testItDoesNotProcessScheduledJobsWhenNewsletterIsNotActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::never(),
      'cron_helper' => $this->cron_helper,
    ], $this);
    // scheduled job is not processed
    $scheduler->process();
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_ACTIVE);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cron_helper' => $this->cron_helper,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItReSchedulesBounceTask() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMonths(1),
    ]);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $finder = $this->makeEmpty(SubscribersFinder::class);
    $scheduler = new Scheduler($finder, $this->logger_factory, $this->cron_helper);

    $scheduler->processScheduledStandardNewsletter($newsletter, $queue);
    $refetched_task = ScheduledTask::where('id', $task->id)->findOne();
    expect($refetched_task->scheduled_at)->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(42));
  }

  public function testItDoesNotReSchedulesBounceTaskWhenSoon() {
    $task = ScheduledTask::createOrUpdate([
      'type' => 'bounce',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addMinute(5),
    ]);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $finder = $this->makeEmpty(SubscribersFinder::class);
    $scheduler = new Scheduler($finder, $this->logger_factory, $this->cron_helper);

    $scheduler->processScheduledStandardNewsletter($newsletter, $queue);
    $refetched_task = ScheduledTask::where('id', $task->id)->findOne();
    expect($refetched_task->scheduled_at)->lessThan(Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHours(1));
  }

  public function testItProcessesScheduledJobsWhenNewsletterIsScheduled() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_SCHEDULED);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->save();

    $scheduler = Stub::make(Scheduler::class, [
      'processScheduledStandardNewsletter' => Expected::once(),
      'cron_helper' => $this->cron_helper,
    ], $this);
    // scheduled job is processed
    $scheduler->process();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToUser() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletter_id = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::now()->subDay(1)->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletter_id)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should have its status set to null (i.e., sending)
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
  }

  public function testItDeletesScheduledAutomaticEmailWhenUserDoesNotExist() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $subscriber = $this->_createSubscriber();
    $task = SendingTask::create();
    $task->newsletter_id = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::now()->subDay(1)->toDateTimeString();
    $task->setSubscribers([$subscriber->id]);
    $task->save();
    $subscriber->delete();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletter_id)->equals($newsletter->id);
    expect($task->getSubscribers())->equals([$subscriber->id]);

    // task should be deleted
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task)->false();
  }

  public function testItProcessesScheduledAutomaticEmailWhenSendingToSegment() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_AUTOMATIC, Newsletter::STATUS_SCHEDULED);
    $segment = $this->_createSegment();
    $subscriber = $this->_createSubscriber();
    $segment_subscriber = $this->_createSubscriberSegment($subscriber->id, $segment->id);
    $options = ['sendTo' => 'segment', 'segment' => $segment->id];
    foreach ($options as $option => $value) {
      $newsletter_option_field = $this->_createNewsletterOptionField($option, Newsletter::TYPE_AUTOMATIC);
      $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, $value);
    }
    $task = SendingTask::create();
    $task->newsletter_id = $newsletter->id;
    $task->status = SendingQueue::STATUS_SCHEDULED;
    $task->scheduled_at = Carbon::now()->subDay(1)->toDateTimeString();
    $task->save();

    // scheduled task should exist
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->equals(SendingQueue::STATUS_SCHEDULED);
    expect($task->newsletter_id)->equals($newsletter->id);

    // task should have its status set to null (i.e., sending)
    $scheduler = new Scheduler(new SubscribersFinder(), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    $task = SendingTask::getByNewsletterId($newsletter->id);
    expect($task->status)->null();
    // task should have 1 subscriber added from segment
    $subscribers = $task->subscribers()->findMany();
    expect($subscribers)->count(1);
    expect($subscribers[0]->id)->equals($subscriber->id);
  }

  public function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(5)->toDateTimeString();
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $queue->updated_at = $originalUpdated;
    $queue->save();
    $scheduler = new Scheduler($this->makeEmpty(SubscribersFinder::class), $this->logger_factory, $this->cron_helper);
    $scheduler->process();
    $newQueue = ScheduledTask::findOne($queue->task_id);
    expect($newQueue->updated_at)->notEquals($originalUpdated);
  }

  public function _createNewsletterSegment($newsletter_id, $segment_id) {
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->newsletter_id = $newsletter_id;
    $newsletter_segment->segment_id = $segment_id;
    $newsletter_segment->save();
    expect($newsletter_segment->getErrors())->false();
    return $newsletter_segment;
  }

  public function _createSubscriberSegment($subscriber_id, $segment_id, $status = 'subscribed') {
    $subscriber_segment = SubscriberSegment::create();
    $subscriber_segment->subscriber_id = $subscriber_id;
    $subscriber_segment->segment_id = $segment_id;
    $subscriber_segment->status = $status;
    $subscriber_segment->save();
    expect($subscriber_segment->getErrors())->false();
    return $subscriber_segment;
  }

  public function _createSegment() {
    $segment = Segment::create();
    $segment->name = 'test';
    $segment->type = 'default';
    $segment->save();
    expect($segment->getErrors())->false();
    return $segment;
  }

  public function _createSubscriber($wp_user_id = null, $status = 'subscribed') {
    $subscriber = Subscriber::create();
    $subscriber->email = 'john@doe.com';
    $subscriber->first_name = 'John';
    $subscriber->last_name = 'Doe';
    $subscriber->wp_user_id = $wp_user_id;
    $subscriber->status = $status;
    $subscriber->save();
    expect($subscriber->getErrors())->false();
    return $subscriber;
  }

  public function _createNewsletter($type = Newsletter::TYPE_NOTIFICATION, $status = 'active') {
    $newsletter = Newsletter::create();
    $newsletter->type = $type;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  public function _createNewsletterOptionField($name, $type) {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = $name;
    $newsletter_option_field->newsletter_type = $type;
    $newsletter_option_field->save();
    expect($newsletter_option_field->getErrors())->false();
    return $newsletter_option_field;
  }

  public function _createNewsletterOption($option_field_id, $newsletter_id, $value) {
    $newsletter_option = NewsletterOption::create();
    $newsletter_option->option_field_id = $option_field_id;
    $newsletter_option->newsletter_id = $newsletter_id;
    $newsletter_option->value = $value;
    $newsletter_option->save();
    expect($newsletter_option->getErrors())->false();
    return $newsletter_option;
  }

  public function _createQueue($newsletter_id, $status = SendingQueue::STATUS_SCHEDULED) {
    $queue = SendingTask::create();
    $queue->status = $status;
    $queue->newsletter_id = $newsletter_id;
    $queue->save();
    expect($queue->getErrors())->false();
    return $queue;
  }

  public function _createOrUpdateWPUser($role = null) {
    $email = 'test@example.com';
    $username = 'phoenix_test_user';
    if (email_exists($email) === false) {
      wp_insert_user(
        [
          'user_login' => $username,
          'user_email' => $email,
          'user_pass' => null,
        ]
      );
    }
    $user = get_user_by('login', $username);
    wp_update_user(
      [
        'ID' => $user->ID,
        'role' => $role,
      ]
    );
    expect($user->ID)->notNull();
    return $user;
  }

  public function _after() {
    Carbon::setTestNow();
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    $this->di_container->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
  }
}
