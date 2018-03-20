<?php
namespace MailPoet\Test\Cron\Workers;

use AspectMock\Test as Mock;
use Carbon\Carbon;
use Codeception\Util\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Scheduler;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Tasks\Sending as SendingTask;

class SchedulerTest extends \MailPoetTest {
  function testItDefinesConstants() {
    expect(Scheduler::UNCONFIRMED_SUBSCRIBER_RESCHEDULE_TIMEOUT)->equals(5);
  }

  function testItConstructs() {
    $scheduler = new Scheduler();
    expect($scheduler->timer)->greaterOrEquals(5);
    $timer = microtime(true) - 2;
    $scheduler = new Scheduler($timer);
    expect($scheduler->timer)->equals($timer);
  }

  function testItThrowsExceptionWhenExecutionLimitIsReached() {
    try {
      $scheduler = new Scheduler(microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT);
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItCanGetScheduledQueues() {
    expect(Scheduler::getScheduledQueues())->isEmpty();
    $queue = SendingTask::create();
    $queue->newsletter_id = 1;
    $queue->status = SendingQueue::STATUS_SCHEDULED;
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    expect(Scheduler::getScheduledQueues())->notEmpty();
  }

  function testItCanCreateNotificationHistory() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $newsletter->save();

    // ensure that notification history does not exist
    $notification_history = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notification_history)->isEmpty();

    // create notification history and ensure that it exists
    $scheduler = new Scheduler();
    $scheduler->createNotificationHistory($newsletter->id);
    $notification_history = Newsletter::where('type', Newsletter::TYPE_NOTIFICATION_HISTORY)
      ->where('parent_id', $newsletter->id)
      ->findOne();
    expect($notification_history)->notEmpty();
  }

  function testItCanDeleteQueueWhenDeliveryIsSetToImmediately() {
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, 'immediately');
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // queue and associated newsletter should be deleted when interval type is set to "immediately"
    expect(SendingQueue::findMany())->notEmpty();
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    expect(SendingQueue::findMany())->count(0);
  }

  function testItCanRescheduleQueueDeliveryTime() {
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field =
      $this->_createNewsletterOptionField('intervalType', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, 'daily');
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // queue's next run date should change when interval type is set to anything
    // other than "immediately"
    $queue = $this->_createQueue($newsletter->id);
    $newsletter_option->value = 'daily';
    $newsletter_option->save();
    $newsletter = Newsletter::filter('filterWithOptions')->findOne($newsletter->id);
    expect($queue->scheduled_at)->null();
    $newsletter->schedule = '0 5 * * *'; // set it to daily at 5
    $scheduler->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    $queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($queue->scheduled_at)->notNull();
  }

  function testItFailsWPSubscriberVerificationWhenSubscriberIsNotAWPUser() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return false and delete queue when subscriber is not a WP user
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  function testItFailsWPSubscriberVerificationWhenSubscriberRoleDoesNotMatch() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return false and delete queue when subscriber role is different from the one
    // specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    expect(count(SendingQueue::findMany()))->equals(0);
  }

  function testItPassesWPSubscriberVerificationWhenSubscriberExistsAndRoleMatches() {
    $WP_user = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($WP_user->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id,
      $newsletter->id, 'author'
    );
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return true when user exists and WP role matches the one specified for the welcome email
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  function testItPassesWPSubscriberVerificationWhenSubscriberHasAnyRole() {
    $WP_user = $this->_createOrUpdateWPUser('author');
    $subscriber = $this->_createSubscriber($WP_user->ID);
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $newsletter_option_field =
      $this->_createNewsletterOptionField('role', Newsletter::TYPE_WELCOME);
    $newsletter_option = $this->_createNewsletterOption(
      $newsletter_option_field->id, $newsletter->id,
      \MailPoet\Newsletter\Scheduler\Scheduler::WORDPRESS_ALL_ROLES);
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // true when user exists and has any role
    $result = $scheduler->verifyWPSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
    expect(count(SendingQueue::findMany()))->greaterOrEquals(1);
  }

  function testItDoesNotProcessWelcomeNewsletterWhenThereAreNoSubscribersToProcess() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers(array());

    // delete queue when the list of subscribers to process is blank
    $scheduler = new Scheduler();
    $result = $scheduler->processWelcomeNewsletter($newsletter, $queue);
    expect($result)->false();
    expect(SendingQueue::findMany())->count(0);
  }

  function testItDoesNotProcessWelcomeNewsletterWhenWPUserCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers(array(1));

    // return false when WP user cannot be verified
    $scheduler = Stub::make(new Scheduler(), array(
      'verifyWPSubscriber' => Stub::exactly(1, function() {
        return false;
      })
    ), $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  function testItDoesNotProcessWelcomeNewsletterWhenSubscriberCannotBeVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers(array(1));

    // return false when subscriber cannot be verified
    $scheduler = Stub::make(new Scheduler(), array(
      'verifyMailpoetSubscriber' => Stub::exactly(1, function() {
        return false;
      })
    ), $this);
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->false();
  }

  function testItProcessesWelcomeNewsletterWhenSubscriberIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'segment';

    // return true when subsriber is verified and update the queue's status to null
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers(array(1));
    $scheduler = Stub::make(new Scheduler(), array(
      'verifyMailpoetSubscriber' => Stub::exactly(1)
    ), $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($updated_queue->status)->null();
  }

  function testItProcessesWelcomeNewsletterWhenWPUserIsVerified() {
    $newsletter = $this->_createNewsletter();
    $newsletter->event = 'user';

    // return true when WP user is verified
    $queue = $this->_createQueue($newsletter->id);
    $queue->setSubscribers(array(1));
    $scheduler = Stub::make(new Scheduler(), array(
      'verifyWPSubscriber' => Stub::exactly(1)
    ), $this);
    expect($queue->status)->notNull();
    expect($scheduler->processWelcomeNewsletter($newsletter, $queue))->true();
    // update queue's status to null
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect($updated_queue->status)->null();
  }

  function testItFailsMailpoetSubscriberVerificationWhenSubscriberDoesNotExist() {
    $scheduler = new Scheduler();
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // return false
    $result = $scheduler->verifyMailpoetSubscriber(null, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber can't be found
    expect(SendingQueue::findMany())->count(0);
  }

  function testItFailsMailpoetSubscriberVerificationWhenSubscriberIsNotInSegment() {
    $subscriber = $this->_createSubscriber();
    $segment = $this->_createSegment();
    $newsletter = $this->_createNewsletter();
    $newsletter_option_field = $this->_createNewsletterOptionField('segment', Newsletter::TYPE_NOTIFICATION);
    $newsletter_option = $this->_createNewsletterOption($newsletter_option_field->id, $newsletter->id, $segment->id);
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // delete queue when subscriber is not in segment specified for the newsletter
    expect(SendingQueue::findMany())->count(0);
  }

  function testItReschedulesQueueDeliveryWhenMailpoetSubscriberHasNotConfirmedSubscription() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return false
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->false();
    // update the time queue is scheduled to run at
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    expect(Carbon::parse($updated_queue->scheduled_at))->equals(
      Carbon::createFromTimestamp(current_time('timestamp'))
        ->addMinutes(Scheduler::UNCONFIRMED_SUBSCRIBER_RESCHEDULE_TIMEOUT)
    );
  }

  function testItCanVerifyMailpoetSubscriber() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();

    // return true after successful verification
    $result = $scheduler->verifyMailpoetSubscriber($subscriber->id, $newsletter, $queue);
    expect($result)->true();
  }

  function testItProcessesScheduledStandardNewsletter() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $queue = $this->_createQueue($newsletter->id);
    $scheduler = new Scheduler();
    $finder = Mock::double('MailPoet\Segments\SubscribersFinder');

    // return true
    expect($scheduler->processScheduledStandardNewsletter($newsletter, $queue))->true();
    // update queue's list of subscribers to process
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    $updated_queue_subscribers = $updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
    expect($updated_queue_subscribers)->equals(array($subscriber->id));
    // set queue's status to null
    expect($updated_queue->status)->null();
    // set newsletter's status to sending
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENDING);
    // SubscribersFinder is used for getting subscribers
    $finder->verifyInvoked('addSubscribersToTaskFromSegments');
  }

  function testItFailsToProcessPostNotificationNewsletterWhenSegmentsDontExist() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);

    // delete or reschedule queue when segments don't exist
    $scheduler = Stub::make(new Scheduler(), array(
      'deleteQueueOrUpdateNextRunDate' => Stub::exactly(1, function() {
        return false;
      })
    ), $this);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  function testItFailsToProcessPostNotificationNewsletterWhenSubscribersNotInSegment() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $segment = $this->_createSegment();
    $newsletter_segment = $this->_createNewsletterSegment($newsletter->id, $segment->id);

    // delete or reschedule queue when there are no subscribers in segments
    $scheduler = Stub::make(new Scheduler(), array(
      'deleteQueueOrUpdateNextRunDate' => Stub::exactly(1, function() {
        return false;
      })
    ), $this);
    expect($scheduler->processPostNotificationNewsletter($newsletter, $queue))->false();
  }

  function testItCanProcessPostNotificationNewsletter() {
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
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($newsletter->id);
    $scheduler = new Scheduler();
    $finder = Mock::double('MailPoet\Segments\SubscribersFinder');

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
    expect($updated_queue_subscribers)->equals(array($subscriber->id));
    expect($updated_queue->newsletter_id)->equals($notification_history->id);
    // set notification history's status to sending
    $updated_notification_history = Newsletter::where('parent_id', $newsletter->id)
      ->findOne();
    expect($updated_notification_history->status)->equals(Newsletter::STATUS_SENDING);
    // SubscribersFinder is used for getting subscribers
    $finder->verifyInvoked('addSubscribersToTaskFromSegments');
  }

  function testItFailsToProcessWhenScheduledQueuesNotFound() {
    $scheduler = new Scheduler();
    expect($scheduler->process())->false();
  }

  function testItDeletesQueueDuringProcessingWhenNewsletterNotFound() {
    $queue = $this->_createQueue(1);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = new Scheduler();
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  function testItDeletesQueueDuringProcessingWhenNewsletterIsSoftDeleted() {
    $newsletter = $this->_createNewsletter();
    $newsletter->deleted_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $newsletter->save();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = new Scheduler();
    $scheduler->process();
    expect(SendingQueue::findMany())->count(0);
  }

  function testItProcessesWelcomeNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_WELCOME);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = Stub::make(new Scheduler(), array(
      'processWelcomeNewsletter' => Stub::exactly(1)
    ), $this);
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function testItProcessesNotificationNewsletters() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = Stub::make(new Scheduler(), array(
      'processPostNotificationNewsletter' => Stub::exactly(1)
    ), $this);
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function testItProcessesStandardScheduledNewsletters() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = Stub::make(new Scheduler(), array(
      'processScheduledStandardNewsletter' => Stub::exactly(1)
    ), $this);
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function testItEnforcesExecutionLimitDuringProcessing() {
    $newsletter = $this->_createNewsletter();
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();
    $scheduler = Stub::make(new Scheduler(), array(
      'processPostNotificationNewsletter' => Stub::exactly(1)
    ), $this);
    $scheduler->timer = microtime(true) - CronHelper::DAEMON_EXECUTION_LIMIT;
    try {
      $scheduler->process();
      self::fail('Maximum execution time limit exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Maximum execution time has been reached.');
    }
  }

  function testItDoesNotProcessScheduledJobsWhenNewsletterIsNotActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_DRAFT);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();

    $scheduler = Stub::make(new Scheduler(), array(
      'processScheduledStandardNewsletter' => Stub::never()
    ), $this);
    // scheduled job is not processed
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function testItProcessesScheduledJobsWhenNewsletterIsActive() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_ACTIVE);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();

    $scheduler = Stub::make(new Scheduler(), array(
      'processScheduledStandardNewsletter' => Stub::once()
    ), $this);
    // scheduled job is processed
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function testItProcessesScheduledJobsWhenNewsletterIsScheduled() {
    $newsletter = $this->_createNewsletter(Newsletter::TYPE_STANDARD, Newsletter::STATUS_SCHEDULED);
    $queue = $this->_createQueue($newsletter->id);
    $queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $queue->save();

    $scheduler = Stub::make(new Scheduler(), array(
      'processScheduledStandardNewsletter' => Stub::once()
    ), $this);
    // scheduled job is processed
    $scheduler->timer = microtime(true);
    $scheduler->process();
  }

  function _createNewsletterSegment($newsletter_id, $segment_id) {
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->newsletter_id = $newsletter_id;
    $newsletter_segment->segment_id = $segment_id;
    $newsletter_segment->save();
    expect($newsletter_segment->getErrors())->false();
    return $newsletter_segment;
  }

  function _createSubscriberSegment($subscriber_id, $segment_id, $status = 'subscribed') {
    $subscriber_segment = SubscriberSegment::create();
    $subscriber_segment->subscriber_id = $subscriber_id;
    $subscriber_segment->segment_id = $segment_id;
    $subscriber_segment->status = $status;
    $subscriber_segment->save();
    expect($subscriber_segment->getErrors())->false();
    return $subscriber_segment;
  }

  function _createSegment() {
    $segment = Segment::create();
    $segment->name = 'test';
    $segment->type = 'default';
    $segment->save();
    expect($segment->getErrors())->false();
    return $segment;
  }

  function _createSubscriber($wp_user_id = null, $status = 'subscribed') {
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

  function _createNewsletter($type = Newsletter::TYPE_NOTIFICATION, $status = 'active') {
    $newsletter = Newsletter::create();
    $newsletter->type = $type;
    $newsletter->status = $status;
    $newsletter->save();
    expect($newsletter->getErrors())->false();
    return $newsletter;
  }

  function _createNewsletterOptionField($name, $type) {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = $name;
    $newsletter_option_field->newsletter_type = $type;
    $newsletter_option_field->save();
    expect($newsletter_option_field->getErrors())->false();
    return $newsletter_option_field;
  }

  function _createNewsletterOption($option_field_id, $newsletter_id, $value) {
    $newsletter_option = NewsletterOption::create();
    $newsletter_option->option_field_id = $option_field_id;
    $newsletter_option->newsletter_id = $newsletter_id;
    $newsletter_option->value = $value;
    $newsletter_option->save();
    expect($newsletter_option->getErrors())->false();
    return $newsletter_option;
  }

  function _createQueue($newsletter_id, $status = SendingQueue::STATUS_SCHEDULED) {
    $queue = SendingTask::create();
    $queue->status = $status;
    $queue->newsletter_id = $newsletter_id;
    $queue->save();
    expect($queue->getErrors())->false();
    return $queue;
  }

  function _createOrUpdateWPUser($role = null) {
    $email = 'test@example.com';
    $username = 'phoenix_test_user';
    if(email_exists($email) === false) {
      wp_insert_user(
        array(
          'user_login' => $username,
          'user_email' => $email,
          'user_pass' => null
        )
      );
    }
    $user = get_user_by('login', $username);
    wp_update_user(
      array(
        'ID' => $user->ID,
        'role' => $role
      )
    );
    expect($user->ID)->notNull();
    return $user;
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
  }
}