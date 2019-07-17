<?php

namespace MailPoet\Test\API\JSON\v1;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\API\JSON\v1\Newsletters;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Url as SubscriptionUrl;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WooCommerce\Helper as WCHelper;

class NewslettersTest extends \MailPoetTest {
  /** @var Newsletters */
  private $endpoint;

  function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Newsletters::class);
    $this->newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'My Standard Newsletter',
        'body' => Fixtures::get('newsletter_body_template'),
        'type' => Newsletter::TYPE_STANDARD,
      ]);

    $this->post_notification = Newsletter::createOrUpdate(
      [
        'subject' => 'My Post Notification',
        'body' => Fixtures::get('newsletter_body_template'),
        'type' => Newsletter::TYPE_NOTIFICATION,
      ]);

    NewsletterOptionField::createOrUpdate(
      [
        'name' => 'isScheduled',
        'newsletter_type' => 'standard',
      ]);
    NewsletterOptionField::createOrUpdate(
      [
        'name' => 'scheduledAt',
        'newsletter_type' => 'standard',
      ]);
  }

  function testItKeepsUnsentNewslettersAtTheTopWhenSortingBySentAtDate() {
    $sent_newsletters = [];
    for ($i = 1; $i <= 3; $i++) {
      $sent_newsletters[$i] = Newsletter::create();
      $sent_newsletters[$i]->type = Newsletter::TYPE_STANDARD;
      $sent_newsletters[$i]->subject = 'Sent newsletter ' . $i;
      $sent_newsletters[$i]->sent_at = '2017-01-0' . $i . ' 01:01:01';
      $sent_newsletters[$i]->save();
    };

    // sorting by ASC order retains unsent newsletters at the top
    $response = $this->endpoint->listing(
      [
        'params' => [
          'type' => 'standard',
        ],
        'sort_by' => 'sent_at',
        'sort_order' => 'asc',
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data[0]['id'])->equals($this->newsletter->id);
    expect($response->data[1]['id'])->equals($sent_newsletters[1]->id);
    expect($response->data[2]['id'])->equals($sent_newsletters[2]->id);
    expect($response->data[3]['id'])->equals($sent_newsletters[3]->id);

    // sorting by DESC order retains unsent newsletters at the top
    $response = $this->endpoint->listing(
      [
        'params' => [
          'type' => 'standard',
        ],
        'sort_by' => 'sent_at',
        'sort_order' => 'desc',
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data[0]['id'])->equals($this->newsletter->id);
    expect($response->data[1]['id'])->equals($sent_newsletters[3]->id);
    expect($response->data[2]['id'])->equals($sent_newsletters[2]->id);
    expect($response->data[3]['id'])->equals($sent_newsletters[1]->id);
  }

  function testItCanGetANewsletter() {
    $response = $this->endpoint->get(); // missing id
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $this->endpoint = new Newsletters(
      ContainerWrapper::getInstance()->get(BulkActionController::class),
      ContainerWrapper::getInstance()->get(Handler::class),
      $wp,
      $this->makeEmpty(WCHelper::class),
      new SettingsController(),
      $this->make(AuthorizedEmailsController::class, ['onNewsletterUpdate' => Expected::never()])
    );
    $response = $this->endpoint->get(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->withSegments()
        ->withOptions()
        ->withSendingQueue()
        ->asArray()
    );
    $hook_name = 'mailpoet_api_newsletters_get_after';
    expect(WPHooksHelper::isFilterApplied($hook_name))->true();
    expect(WPHooksHelper::getFilterApplied($hook_name)[0])->internalType('array');
  }

  function testItCanSaveANewNewsletter() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'some_option';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_STANDARD;
    $newsletter_option_field->save();

    $valid_data = [
      'subject' => 'My First Newsletter',
      'type' => Newsletter::TYPE_STANDARD,
      'options' => [
        $newsletter_option_field->name => 'some_option_value',
      ],
    ];

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = new Newsletters(
      ContainerWrapper::getInstance()->get(BulkActionController::class),
      ContainerWrapper::getInstance()->get(Handler::class),
      $wp,
      $this->makeEmpty(WCHelper::class),
      new SettingsController(),
      $this->make(AuthorizedEmailsController::class, ['onNewsletterUpdate' => Expected::once()])
    );

    $response = $this->endpoint->save($valid_data);
    $saved_newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_STANDARD)
      ->findOne($response->data['id']);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($saved_newsletter->asArray());
    // newsletter option should be saved
    expect($saved_newsletter->some_option)->equals('some_option_value');

    $hook_name = 'mailpoet_api_newsletters_save_before';
    expect(WPHooksHelper::isFilterApplied($hook_name))->true();
    expect(WPHooksHelper::getFilterApplied($hook_name)[0])->internalType('array');
    $hook_name = 'mailpoet_api_newsletters_save_after';
    expect(WPHooksHelper::isActionDone($hook_name))->true();
    expect(WPHooksHelper::getActionDone($hook_name)[0] instanceof Newsletter)->true();

    $invalid_data = [
      'subject' => 'Missing newsletter type',
    ];

    $response = $this->endpoint->save($invalid_data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a type.');
  }

  function testItCanSaveAnExistingNewsletter() {
    $newsletter_data = [
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter',
    ];

    $response = $this->endpoint->save($newsletter_data);
    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($updated_newsletter->asArray());
    expect($updated_newsletter->subject)->equals('My Updated Newsletter');
  }

  function testItDoesNotRerenderPostNotificationsUponUpdate() {
    $sending_queue = SendingTask::create();
    $sending_queue->newsletter_id = $this->post_notification->id;
    $sending_queue->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue->newsletter_rendered_body = null;
    $sending_queue->newsletter_rendered_subject = null;
    $sending_queue->save();
    expect($sending_queue->getErrors())->false();

    $newsletter_data = [
      'id' => $this->post_notification->id,
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $response = $this->endpoint->save($newsletter_data);
    $updated_queue = SendingQueue::where('newsletter_id', $this->post_notification->id)
      ->findOne()
      ->asArray();

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($updated_queue['newsletter_rendered_body'])->null();
    expect($updated_queue['newsletter_rendered_subject'])->null();
  }

  function testItCanRerenderQueueUponSave() {
    $sending_queue = SendingTask::create();
    $sending_queue->newsletter_id = $this->newsletter->id;
    $sending_queue->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue->newsletter_rendered_body = null;
    $sending_queue->newsletter_rendered_subject = null;
    $sending_queue->save();
    expect($sending_queue->getErrors())->false();

    $newsletter_data = [
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $response = $this->endpoint->save($newsletter_data);
    $updated_queue = SendingQueue::where('newsletter_id', $this->newsletter->id)
      ->findOne()
      ->asArray();

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($updated_queue['newsletter_rendered_body'])->hasKey('html');
    expect($updated_queue['newsletter_rendered_body'])->hasKey('text');
    expect($updated_queue['newsletter_rendered_subject'])->equals('My Updated Newsletter');
  }

  function testItCanUpdatePostNotificationScheduleUponSave() {
    $newsletter_options = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];
    foreach ($newsletter_options as $option) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
      $newsletter_option_field->save();
    }

    $newsletter_data = [
      'id' => $this->newsletter->id,
      'type' => Newsletter::TYPE_NOTIFICATION,
      'subject' => 'Newsletter',
      'options' => [
        'intervalType' => Scheduler::INTERVAL_WEEKLY,
        'timeOfDay' => '50400',
        'weekDay' => '1',
        'monthDay' => '0',
        'nthWeekDay' => '1',
        'schedule' => '0 14 * * 1',
      ],
    ];
    $response = $this->endpoint->save($newsletter_data);
    $saved_newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($response->data['id']);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($saved_newsletter->asArray());

    // schedule should be recalculated when options change
    $newsletter_data['options']['intervalType'] = Scheduler::INTERVAL_IMMEDIATELY;
    $response = $this->endpoint->save($newsletter_data);
    $saved_newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_NOTIFICATION)
      ->findOne($response->data['id']);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($saved_newsletter->schedule)->equals('* * * * *');
  }

  function testItCanReschedulePreviouslyScheduledSendingQueueJobs() {
    // create newsletter options
    $newsletter_options = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];
    foreach ($newsletter_options as $option) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
      $newsletter_option_field->save();
    }

    // create sending queues
    $current_time = Carbon::now();
    $sending_queue_1 = SendingTask::create();
    $sending_queue_1->newsletter_id = 1;
    $sending_queue_1->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue_1->scheduled_at = $current_time;
    $sending_queue_1->save();

    $sending_queue_2 = SendingTask::create();
    $sending_queue_2->newsletter_id = 1;
    $sending_queue_2->save();

    // save newsletter via router
    $newsletter_data = [
      'id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION,
      'subject' => 'Newsletter',
      'options' => [
        // weekly on Monday @ 7am
        'intervalType' => Scheduler::INTERVAL_WEEKLY,
        'timeOfDay' => '25200',
        'weekDay' => '1',
        'monthDay' => '0',
        'nthWeekDay' => '1',
        'schedule' => '0 7 * * 1',
      ],
    ];
    $newsletter = $this->endpoint->save($newsletter_data);
    $sending_queue_1 = SendingTask::createFromQueue(SendingQueue::findOne($sending_queue_1->id));
    $sending_queue_2 = SendingTask::createFromQueue(SendingQueue::findOne($sending_queue_2->id));
    expect($sending_queue_1->scheduled_at)->notEquals($current_time);
    expect($sending_queue_1->scheduled_at)->equals(
      Scheduler::getNextRunDate($newsletter->data['schedule'])
    );
    expect($sending_queue_2->scheduled_at)->null();
  }

  function testItCanModifySegmentsOfExistingNewsletter() {
    $segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $fake_segment_id = 1;

    $newsletter_data = [
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter',
      'segments' => [
        $segment_1->asArray(),
        $fake_segment_id,
      ],
    ];

    $response = $this->endpoint->save($newsletter_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $updated_newsletter =
      Newsletter::findOne($this->newsletter->id)
        ->withSegments();

    expect(count($updated_newsletter->segments))->equals(1);
    expect($updated_newsletter->segments[0]['name'])->equals('Segment 1');
  }

  function testItCanSetANewsletterStatus() {
    // set status to sending
    $response = $this->endpoint->setStatus
    ([
       'id' => $this->newsletter->id,
       'status' => Newsletter::STATUS_SENDING,
     ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['status'])->equals(Newsletter::STATUS_SENDING);

    // set status to draft
    $response = $this->endpoint->setStatus(
      [
        'id' => $this->newsletter->id,
        'status' => Newsletter::STATUS_DRAFT,
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['status'])->equals(Newsletter::STATUS_DRAFT);

    // no status specified throws an error
    $response = $this->endpoint->setStatus(
      [
        'id' => $this->newsletter->id,
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('You need to specify a status.');

    // invalid newsletter id throws an error
    $response = $this->endpoint->setStatus(
      [
        'status' => Newsletter::STATUS_DRAFT,
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This email does not exist.');
  }

  function testItReschedulesPastDuePostNotificationsWhenStatusIsSetBackToActive() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();
    $schedule = sprintf('0 %d * * *', Carbon::createFromTimestamp(current_time('timestamp'))->hour); // every day at current hour
    $random_future_date = Carbon::createFromTimestamp(current_time('timestamp'))->addDays(10)->format('Y-m-d H:i:s'); // 10 days from now
    $newsletter_option = NewsletterOption::createOrUpdate(
      [
        'newsletter_id' => $this->post_notification->id,
        'option_field_id' => $newsletter_option_field->id,
        'value' => $schedule,
      ]
    );
    $sending_queue_1 = SendingTask::create();
    $sending_queue_1->newsletter_id = $this->post_notification->id;
    $sending_queue_1->scheduled_at = Scheduler::getPreviousRunDate($schedule);
    $sending_queue_1->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue_1->save();
    $sending_queue_2 = SendingTask::create();
    $sending_queue_2->newsletter_id = $this->post_notification->id;
    $sending_queue_2->scheduled_at = $random_future_date;
    $sending_queue_2->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue_2->save();
    $sending_queue_3 = SendingTask::create();
    $sending_queue_3->newsletter_id = $this->post_notification->id;
    $sending_queue_3->scheduled_at = Scheduler::getPreviousRunDate($schedule);
    $sending_queue_3->save();

    $this->endpoint->setStatus(
      [
        'id' => $this->post_notification->id,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $tasks = ScheduledTask::findMany();
    // previously scheduled notification is rescheduled for future date
    expect($tasks[0]->scheduled_at)->equals(Scheduler::getNextRunDate($schedule));
    // future scheduled notifications are left intact
    expect($tasks[1]->scheduled_at)->equals($random_future_date);
    // previously unscheduled (e.g., sent/sending) notifications are left intact
    expect($tasks[2]->scheduled_at)->equals(Scheduler::getPreviousRunDate($schedule));
  }

  function testItSchedulesPostNotificationsWhenStatusIsSetBackToActive() {
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->name = 'schedule';
    $newsletter_option_field->newsletter_type = Newsletter::TYPE_NOTIFICATION;
    $newsletter_option_field->save();
    $schedule = '* * * * *';
    NewsletterOption::createOrUpdate(
      [
        'newsletter_id' => $this->post_notification->id,
        'option_field_id' => $newsletter_option_field->id,
        'value' => $schedule,
      ]
    );

    $this->endpoint->setStatus(
      [
        'id' => $this->post_notification->id,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $tasks = ScheduledTask::findMany();
    expect($tasks)->notEmpty();
  }

  function testItCanRestoreANewsletter() {
    $this->newsletter->trash();

    $trashed_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($trashed_newsletter->deleted_at)->notNull();

    $response = $this->endpoint->restore(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashANewsletter() {
    $response = $this->endpoint->trash(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteANewsletter() {
    $response = $this->endpoint->delete(['id' => $this->newsletter->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDuplicateANewsletter() {
    $wp = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = new Newsletters(
      ContainerWrapper::getInstance()->get(BulkActionController::class),
      ContainerWrapper::getInstance()->get(Handler::class),
      $wp,
      $this->makeEmpty(WCHelper::class),
      new SettingsController(),
      $this->make(AuthorizedEmailsController::class, ['onNewsletterUpdate' => Expected::never()])
    );

    $response = $this->endpoint->duplicate(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Standard Newsletter')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);

    $hook_name = 'mailpoet_api_newsletters_duplicate_after';
    expect(WPHooksHelper::isActionDone($hook_name))->true();
    expect(WPHooksHelper::getActionDone($hook_name)[0] instanceof Newsletter)->true();

    $response = $this->endpoint->duplicate(['id' => $this->post_notification->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Post Notification')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  function testItCanCreateANewsletter() {
    $data = [
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD,
    ];
    $response = $this->endpoint->create($data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'My New Newsletter')
        ->findOne()
        ->asArray()
    );

    $response = $this->endpoint->create();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a type.');
  }

  function testItCanGetListingData() {
    $segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment_2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment_1->id,
      ]
    );
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment_2->id,
      ]
    );
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(
      [
        'newsletter_id' => $this->post_notification->id,
        'segment_id' => $segment_2->id,
      ]
    );
    $newsletter_segment->save();

    $response = $this->endpoint->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(2);

    expect($response->data)->count(2);
    expect($response->data[0]['subject'])->equals('My Standard Newsletter');
    expect($response->data[1]['subject'])->equals('My Post Notification');

    // 1st subscriber has 2 segments
    expect($response->data[0]['segments'])->count(2);
    expect($response->data[0]['segments'][0]['id'])
      ->equals($segment_1->id);
    expect($response->data[0]['segments'][1]['id'])
      ->equals($segment_2->id);

    // 2nd subscriber has 1 segment
    expect($response->data[1]['segments'])->count(1);
    expect($response->data[1]['segments'][0]['id'])
      ->equals($segment_2->id);
  }

  function testItCanFilterListing() {
    // create 2 segments
    $segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment_2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    // link standard newsletter to the 2 segments
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment_1->id,
      ]
    );
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate
    ([
       'newsletter_id' => $this->newsletter->id,
       'segment_id' => $segment_2->id,
     ]
    );
    $newsletter_segment->save();

    // link post notification to the 2nd segment
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(
      [
        'newsletter_id' => $this->post_notification->id,
        'segment_id' => $segment_2->id,
      ]
    );
    $newsletter_segment->save();

    // filter by 1st segment
    $response = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => $segment_1->id,
        ],
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);

    // we should only get the standard newsletter
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['subject'])->equals($this->newsletter->subject);

    // filter by 2nd segment
    $response = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => $segment_2->id,
        ],
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);

    // we should have the 2 newsletters
    expect($response->meta['count'])->equals(2);
  }

  function testItCanLimitListing() {
    // get 1st page (limit items per page to 1)
    $response = $this->endpoint->listing(
      [
        'limit' => 1,
        'sort_by' => 'subject',
        'sort_order' => 'asc',
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['subject'])->equals(
      $this->post_notification->subject
    );

    // get 1st page (limit items per page to 1)
    $response = $this->endpoint->listing(
      [
        'limit' => 1,
        'offset' => 1,
        'sort_by' => 'subject',
        'sort_order' => 'asc',
      ]
    );

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['subject'])->equals(
      $this->newsletter->subject
    );
  }

  function testItCanBulkDeleteSelectionOfNewsletters() {
    $selection_ids = [
      $this->newsletter->id,
      $this->post_notification->id,
    ];

    $response = $this->endpoint->bulkAction(
      [
        'listing' => [
          'selection' => $selection_ids,
        ],
        'action' => 'delete',
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(count($selection_ids));
  }

  function testItCanBulkDeleteNewsletters() {
    $response = $this->endpoint->bulkAction(
      [
        'action' => 'trash',
        'listing' => ['group' => 'all'],
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction(
      [
        'action' => 'delete',
        'listing' => ['group' => 'trash'],
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction(
      [
        'action' => 'delete',
        'listing' => ['group' => 'trash'],
      ]
    );
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function testItCanSendAPreview() {
    $subscriber = 'test@subscriber.com';
    $unsubscribeLink = SubscriptionUrl::getUnsubscribeUrl(null);
    $manageLink = SubscriptionUrl::getManageUrl(null);
    $viewInBrowserLink = Url::getViewInBrowserUrl(null, $this->newsletter, false, false, true);
    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->id,
      'mailer' => Stub::makeEmpty(
        '\MailPoet\Mailer\Mailer',
        [
          'send' => function($newsletter, $subscriber, $extra_params)
            use ($unsubscribeLink, $manageLink, $viewInBrowserLink)
          {
            expect(is_array($newsletter))->true();
            expect($newsletter['body']['text'])->contains('Hello test');
            expect($subscriber)->equals($subscriber);
            expect($extra_params['unsubscribe_url'])->equals(home_url());
            // system links are replaced with hashes
            expect($newsletter['body']['html'])->contains('href="' . $viewInBrowserLink . '">View in browser');
            expect($newsletter['body']['html'])->contains('href="' . $unsubscribeLink . '">Unsubscribe');
            expect($newsletter['body']['html'])->contains('href="' . $manageLink . '">Manage subscription');
            return ['response' => true];
          },
        ]
      ),
    ];
    $response = $this->endpoint->sendPreview($data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItReturnsMailerErrorWhenSendingFailed() {
    $subscriber = 'test@subscriber.com';
    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->id,
      'mailer' => Stub::makeEmpty(
        '\MailPoet\Mailer\Mailer',
        [
          'send' => function($newsletter, $subscriber) {
            expect(is_array($newsletter))->true();
            expect($newsletter['body']['text'])->contains('Hello test');
            expect($subscriber)->equals($subscriber);
            return [
              'response' => false,
              'error' => Stub::make(
                '\MailPoet\Mailer\MailerError',
                [
                  'getMessage' => 'failed',
                ]
              ),
            ];
          },
        ]
      ),
    ];
    $response = $this->endpoint->sendPreview($data);
    expect($response->errors[0]['message'])->equals('The email could not be sent: failed');
  }

  function testItReturnsBrowserPreviewUrlWithoutProtocol() {
    $data = [
      'id' => $this->newsletter->id,
      'body' => 'fake body',
    ];
    $response = $this->endpoint->showPreview($data);
    expect($response->meta['preview_url'])->notContains('http');
    expect($response->meta['preview_url'])->regExp('!^\/\/!');
  }

  function testItGeneratesPreviewLinksWithNewsletterHashAndNoSubscriberData() {
    $response = $this->endpoint->listing();
    $preview_link = $response->data[0]['preview_url'];
    parse_str(parse_url($preview_link, PHP_URL_QUERY), $preview_link_data);
    $preview_link_data = Url::transformUrlDataObject(Router::decodeRequestData($preview_link_data['data']));
    expect($preview_link_data['newsletter_hash'])->notEmpty();
    expect($preview_link_data['subscriber_id'])->false();
    expect($preview_link_data['subscriber_token'])->false();
    expect((boolean)$preview_link_data['preview'])->true();
  }

  function testItDeletesSendingQueueAndSetsNewsletterStatusToDraftWhenItIsUnscheduled() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $sending_queue = SendingTask::create();
    $sending_queue->newsletter_id = $newsletter->id;
    $sending_queue->newsletter_rendered_body = [
      'html' => 'html',
      'text' => 'text',
    ];
    $sending_queue->status = SendingQueue::STATUS_SCHEDULED;
    $sending_queue->scheduled_at = Carbon::now()->format('Y-m-d H:i');
    $sending_queue->save();
    expect($sending_queue->getErrors())->false();

    $newsletter_data = [
      'id' => $newsletter->id,
      'options' => [
        'isScheduled' => false,
      ],
    ];

    $this->endpoint->save($newsletter_data);
    $newsletter = Newsletter::findOne($newsletter->id);
    $sending_queue = SendingQueue::findOne($sending_queue->id);
    expect($newsletter->status)->equals(Newsletter::STATUS_DRAFT);
    expect($sending_queue)->false();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
