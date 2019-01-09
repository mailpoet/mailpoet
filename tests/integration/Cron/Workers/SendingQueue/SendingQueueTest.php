<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue;

use AspectMock\Test as Mock;
use Carbon\Carbon;
use Codeception\Util\Fixtures;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Populator;
use MailPoet\Cron\Workers\SendingQueue\SendingErrorHandler;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\StatsNotifications\Scheduler as StatsNotificationsScheduler;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Router\Router;
use MailPoet\Subscription\Url;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Hooks;

class SendingQueueTest extends \MailPoetTest {
  /** @var SendingErrorHandler */
  private $sending_error_handler;

  /** @var Scheduler */
  private $stats_notifications_worker;

  function _before() {
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    $populator = new Populator();
    $populator->up();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'john@doe.com';
    $this->subscriber->first_name = 'John';
    $this->subscriber->last_name = 'Doe';
    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->save();
    $this->segment = Segment::create();
    $this->segment->name = 'segment';
    $this->segment->save();
    $this->subscriber_segment = SubscriberSegment::create();
    $this->subscriber_segment->subscriber_id = $this->subscriber->id;
    $this->subscriber_segment->segment_id = $this->segment->id;
    $this->subscriber_segment->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->status = Newsletter::STATUS_ACTIVE;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->save();
    $this->newsletter_segment = NewsletterSegment::create();
    $this->newsletter_segment->newsletter_id = $this->newsletter->id;
    $this->newsletter_segment->segment_id = $this->segment->id;
    $this->newsletter_segment->save();
    $this->queue = SendingTask::create();
    $this->queue->newsletter_id = $this->newsletter->id;
    $this->queue->setSubscribers(array($this->subscriber->id));
    $this->queue->count_total = 1;
    $this->queue->save();
    $this->newsletter_link = NewsletterLink::create();
    $this->newsletter_link->newsletter_id = $this->newsletter->id;
    $this->newsletter_link->queue_id = $this->queue->id;
    $this->newsletter_link->url = '[link:subscription_unsubscribe_url]';
    $this->newsletter_link->hash = 'abcde';
    $this->newsletter_link->save();
    $this->sending_error_handler = new SendingErrorHandler();
    $this->stats_notifications_worker = new StatsNotificationsScheduler();
    $this->sending_queue_worker = new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker);
  }

  private function getDirectUnsubscribeURL() {
    return Url::getUnsubscribeUrl($this->subscriber);
  }

  private function getTrackedUnsubscribeURL() {
    $data = Links::createUrlDataObject(
      $this->subscriber->id,
      $this->subscriber->email,
      $this->queue->id,
      $this->newsletter_link->hash,
      false
    );
    return Router::buildRequest(
      Track::ENDPOINT,
      Track::ACTION_CLICK,
      $data
    );
  }

  function testItConstructs() {
    expect($this->sending_queue_worker->batch_size)->equals(SendingQueueWorker::BATCH_SIZE);
    expect($this->sending_queue_worker->mailer_task instanceof MailerTask);
    expect($this->sending_queue_worker->newsletter_task instanceof NewsletterTask);
    expect(strlen($this->sending_queue_worker->timer))->greaterOrEquals(5);

    // constructor accepts timer argument
    $timer = microtime(true) - 5;
    $sending_queue_worker = new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker, $timer);
    expect($sending_queue_worker->timer)->equals($timer);
  }

  function testItEnforcesExecutionLimitsBeforeQueueProcessing() {
    $sending_queue_worker = Stub::make(
      new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker),
      array(
        'processQueue' => Expected::never(),
        'enforceSendingAndExecutionLimits' => Expected::exactly(1, function() {
          throw new \Exception();
        })
      ), $this);
    $sending_queue_worker->__construct($this->sending_error_handler, $this->stats_notifications_worker);
    try {
      $sending_queue_worker->process();
      self::fail('Execution limits function was not called.');
    } catch(\Exception $e) {
      // No exception handling needed
    }
  }

  function testItEnforcesExecutionLimitsAfterSendingWhenQueueStatusIsNotSetToComplete() {
    $sending_queue_worker = Stub::make(
      new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker),
      array(
        'enforceSendingAndExecutionLimits' => Expected::exactly(1)
      ), $this);
    $sending_queue_worker->__construct(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'sendBulk' => null
        )
      )
    );
    $sending_queue_worker->sendNewsletters(
      $this->queue,
      $prepared_subscribers = array(),
      $prepared_newsletters = [],
      $prepared_subscribers = [],
      $statistics[] = array(
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $this->queue->id
      )
    );
  }

  function testItDoesNotEnforceExecutionLimitsAfterSendingWhenQueueStatusIsSetToComplete() {
    // when sending is done and there are no more subscribers to process, continue
    // without enforcing execution limits. this allows the newsletter to be marked as sent
    // in the process() method and after that execution limits will be enforced
    $queue = $this->queue;
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $sending_queue_worker = Stub::make(
      new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker),
      array(
        'enforceSendingAndExecutionLimits' => Expected::never()
      ), $this);
    $sending_queue_worker->__construct(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'sendBulk' => null
        )
      )
    );
    $sending_queue_worker->sendNewsletters(
      $queue,
      $prepared_subscribers = array(),
      $prepared_newsletters = array(),
      $prepared_subscribers = array(),
      $statistics[] = array(
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => $queue->id
      )
    );
  }

  function testItEnforcesExecutionLimitsAfterQueueProcessing() {
    $sending_queue_worker = Stub::make(
      new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker),
      array(
        'processQueue' => function() {
          // this function returns a queue object
          return (object)array('status' => null);
        },
        'enforceSendingAndExecutionLimits' => Expected::exactly(2)
      ), $this);
    $sending_queue_worker->__construct($this->sending_error_handler, $this->stats_notifications_worker);
    $sending_queue_worker->process();
  }

  function testItDeletesQueueWhenNewsletterIsNotFound() {
    // queue exists
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->notEquals(false);

    // delete newsletter
    Newsletter::findOne($this->newsletter->id)
      ->delete();

    // queue no longer exists
    $this->sending_queue_worker->process();
    $queue = SendingQueue::findOne($this->queue->id);
    expect($queue)->false(false);
  }

  function testItPassesExtraParametersToMailerWhenTrackingIsDisabled() {
    Setting::setValue('tracking.enabled', false);
    $directUnsubscribeURL = $this->getDirectUnsubscribeURL();
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extra_params) use ($directUnsubscribeURL) {
            expect(isset($extra_params['unsubscribe_url']))->true();
            expect($extra_params['unsubscribe_url'])->equals($directUnsubscribeURL);
            return true;
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();
  }

  function testItPassesExtraParametersToMailerWhenTrackingIsEnabled() {
    Setting::setValue('tracking.enabled', true);
    $trackedUnsubscribeURL = $this->getTrackedUnsubscribeURL();
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extra_params) use ($trackedUnsubscribeURL) {
            expect(isset($extra_params['unsubscribe_url']))->true();
            expect($extra_params['unsubscribe_url'])->equals($trackedUnsubscribeURL);
            return true;
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();
  }

  function testItCanProcessSubscribersOneByOne() {
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::exactly(1, function($newsletter, $subscriber, $extra_params) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return true;
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();

    // newsletter status is set to sent
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array($this->subscriber->id));
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItCanProcessSubscribersInBulk() {
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'sendBulk' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter[0]['body']['html']))->true();
            expect(!empty($newsletter[0]['body']['text']))->true();
            return true;
          }),
          'getProcessingMethod' => Expected::exactly(1, function() {
            return 'bulk';
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();

    // newsletter status is set to sent
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array($this->subscriber->id));
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItProcessesStandardNewsletters() {
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return true;
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();

    // queue status is set to completed
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // newsletter status is set to sent and sent_at date is populated
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updated_newsletter->sent_at)->equals($updated_queue->processed_at);

    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array($this->subscriber->id));
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItUpdatesUpdateTime() {
    $originalUpdated = Carbon::createFromTimestamp(current_time('timestamp'))->subHours(5)->toDateTimeString();

    $this->queue->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
    $this->queue->updated_at = $originalUpdated;
    $this->queue->save();

    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletter_segment->delete();

    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::makeEmpty(new MailerTask(), array(), $this)
    );
    $sending_queue_worker->process();

    $newQueue = ScheduledTask::findOne($this->queue->task_id);
    expect($newQueue->updated_at)->notEquals($originalUpdated);
  }

  function testItCanProcessWelcomeNewsletters() {
    $this->newsletter->type = Newsletter::TYPE_WELCOME;
    $this->newsletter_segment->delete();

    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::exactly(1, function($newsletter, $subscriber) {
            // newsletter body should not be empty
            expect(!empty($newsletter['body']['html']))->true();
            expect(!empty($newsletter['body']['text']))->true();
            return true;
          })
        ),
        $this
      )
    );
    $sending_queue_worker->process();

    // newsletter status is set to sent
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // queue status is set to completed
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect($updated_queue->status)->equals(SendingQueue::STATUS_COMPLETED);

    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array($this->subscriber->id));
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created
    $statistics = StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)
      ->where('subscriber_id', $this->subscriber->id)
      ->where('queue_id', $this->queue->id)
      ->findOne();
    expect($statistics)->notEquals(false);
  }

  function testItRemovesNonexistentSubscribersFromProcessingList() {
    $queue = $this->queue;
    $queue->setSubscribers(array(
      $this->subscriber->id(),
      12345645454
    ));
    $queue->count_total = 2;
    $queue->save();
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array(
        'send' => Expected::exactly(1, function() {
          return true;
        })
      ),
      $this
    );
    $sending_queue_worker->process();

    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array($this->subscriber->id));
    expect($updated_queue->count_total)->equals(1);
    expect($updated_queue->count_processed)->equals(1);
    expect($updated_queue->count_to_process)->equals(0);

    // statistics entry should be created only for 1 subscriber
    $statistics = StatisticsNewsletters::findMany();
    expect(count($statistics))->equals(1);
  }

  function testItUpdatesQueueSubscriberCountWhenNoneOfSubscribersExist() {
    $queue = $this->queue;
    $queue->setSubscribers(array(
      123,
      456
    ));
    $queue->count_total = 2;
    $queue->save();
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => true)
    );
    $sending_queue_worker->process();

    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($queue->id));
    // queue subscriber processed/to process count is updated
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_UNPROCESSED))
      ->equals(array());
    expect($updated_queue->getSubscribers(ScheduledTaskSubscriber::STATUS_PROCESSED))
      ->equals(array());
    expect($updated_queue->count_total)->equals(0);
    expect($updated_queue->count_processed)->equals(0);
    expect($updated_queue->count_to_process)->equals(0);
  }

  function testItDoesNotSendToTrashedSubscribers() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => true)
    );

    // newsletter is sent to existing subscriber
    $sending_queue_worker->process();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect((int)$updated_queue->count_total)->equals(1);

    // newsletter is not sent to trashed subscriber
    $this->_after();
    $this->_before();
    $subscriber = $this->subscriber;
    $subscriber->deleted_at = Carbon::now();
    $subscriber->save();
    $sending_queue_worker->process();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect((int)$updated_queue->count_total)->equals(0);
  }

  function testItDoesNotSendToGloballyUnsubscribedSubscribers() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => true)
    );

    // newsletter is not sent to globally unsubscribed subscriber
    $this->_after();
    $this->_before();
    $subscriber = $this->subscriber;
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->save();
    $sending_queue_worker->process();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect((int)$updated_queue->count_total)->equals(0);
  }

  function testItDoesNotSendToSubscribersUnsubscribedFromSegments() {
    $sending_queue_worker = $this->sending_queue_worker;
    $sending_queue_worker->mailer_task = Stub::make(
      new MailerTask(),
      array('send' => true)
    );

    // newsletter is not sent to subscriber unsubscribed from segment
    $this->_after();
    $this->_before();
    $subscriber_segment = $this->subscriber_segment;
    $subscriber_segment->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber_segment->save();
    $sending_queue_worker->process();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    expect((int)$updated_queue->count_total)->equals(0);
  }

  function testItPausesSendingWhenProcessedSubscriberListCannotBeUpdated() {
    $sending_task = Mock::double(SendingTask::create(), array(
      'updateProcessedSubscribers' => false
    ));
    $sending_task->id = 100;
    $sending_queue_worker = Stub::make(new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker));
    $sending_queue_worker->__construct(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'sendBulk' => true
        )
      )
    );
    try {
      $sending_queue_worker->sendNewsletters(
        $sending_task->getObject(),
        $prepared_subscribers = [],
        $prepared_newsletters = [],
        $prepared_subscribers = [],
        $statistics = []
      );
      $this->fail('Paused sending exception was not thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Sending has been paused.');
    }
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
    expect($mailer_log['error'])->equals(
      array(
        'operation' => 'processed_list_update',
        'error_message' => 'QUEUE-100-PROCESSED-LIST-UPDATE'
      )
    );
  }

  function testItDoesNotUpdateNewsletterHashDuringSending() {
    $sending_queue_worker = new SendingQueueWorker(
      $this->sending_error_handler,
      $this->stats_notifications_worker,
      $timer = false,
      Stub::make(
        new MailerTask(),
        array(
          'send' => Expected::once()
        ),
        $this
      )
    );
    $sending_queue_worker->process();

    // newsletter is sent and hash remains intact
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updated_newsletter->hash)->equals($this->newsletter->hash);
  }

  function testItAllowsSettingCustomBatchSize() {
    $custom_batch_size_value = 10;
    $filter = function() use ($custom_batch_size_value) {
      return $custom_batch_size_value;
    };
    Hooks::addFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
    $sending_queue_worker = new SendingQueueWorker($this->sending_error_handler, $this->stats_notifications_worker);
    expect($sending_queue_worker->batch_size)->equals($custom_batch_size_value);
    Hooks::removeFilter('mailpoet_cron_worker_sending_queue_batch_size', $filter);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsNewsletters::$_table);
  }
}
