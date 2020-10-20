<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\JSON\v1\Newsletters;
use MailPoet\Cron\CronHelper;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Listing\Handler;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Idiorm\ORM;

class NewslettersTest extends \MailPoetTest {
  public $postNotification;
  /** @var Newsletter */
  public $newsletter;
  /** @var Newsletters */
  private $endpoint;

  /** @var CronHelper */
  private $cronHelper;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  public function _before() {
    parent::_before();
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->endpoint = Stub::copy(
      ContainerWrapper::getInstance()->get(Newsletters::class),
      [
        'newslettersResponseBuilder' => new NewslettersResponseBuilder(
          $this->diContainer->get(EntityManager::class),
          new NewslettersRepository($this->diContainer->get(EntityManager::class)),
          new NewsletterStatisticsRepository(
            $this->diContainer->get(EntityManager::class),
            $this->makeEmpty(WCHelper::class)
          )
        ),
      ]
    );
    $this->newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'My Standard Newsletter',
        'body' => Fixtures::get('newsletter_body_template'),
        'type' => Newsletter::TYPE_STANDARD,
      ]);

    $this->postNotification = Newsletter::createOrUpdate(
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

  public function testItKeepsUnsentNewslettersAtTheTopWhenSortingBySentAtDate() {
    $sentNewsletters = [];
    for ($i = 1; $i <= 3; $i++) {
      $sentNewsletters[$i] = Newsletter::create();
      $sentNewsletters[$i]->type = Newsletter::TYPE_STANDARD;
      $sentNewsletters[$i]->subject = 'Sent newsletter ' . $i;
      $sentNewsletters[$i]->sentAt = '2017-01-0' . $i . ' 01:01:01';
      $sentNewsletters[$i]->save();
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
    expect($response->data[1]['id'])->equals($sentNewsletters[1]->id);
    expect($response->data[2]['id'])->equals($sentNewsletters[2]->id);
    expect($response->data[3]['id'])->equals($sentNewsletters[3]->id);

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
    expect($response->data[1]['id'])->equals($sentNewsletters[3]->id);
    expect($response->data[2]['id'])->equals($sentNewsletters[2]->id);
    expect($response->data[3]['id'])->equals($sentNewsletters[1]->id);
  }

  public function testItCanGetANewsletter() {
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
    $this->endpoint = $this->createNewslettersEndpointWithMocks([
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
      'subscribersFeature' => Stub::make(SubscribersFeature::class),
    ]);
    $response = $this->endpoint->get(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->withSegments()
        ->withOptions()
        ->withSendingQueue()
        ->asArray()
    );
    $hookName = 'mailpoet_api_newsletters_get_after';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->array();
  }

  public function testItCanSaveANewsletter() {
    $newsletterData = [
      'id' => $this->newsletter->id,
      'type' => 'Updated type',
      'subject' => 'Updated subject',
      'preheader' => 'Updated preheader',
      'body' => '{"value": "Updated body"}',
      'sender_name' => 'Updated sender name',
      'sender_address' => 'Updated sender address',
      'reply_to_name' => 'Updated reply-to name',
      'reply_to_address' => 'Updated reply-to address',
      'ga_campaign' => 'Updated GA campaign',
    ];

    $response = $this->endpoint->save($newsletterData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $updatedNewsletter = Newsletter::findOne($this->newsletter->id);
    assert($updatedNewsletter instanceof Newsletter); // PHPStan
    expect($response->data)->equals($updatedNewsletter->asArray());
    expect($updatedNewsletter->type)->equals('Updated type');
    expect($updatedNewsletter->subject)->equals('Updated subject');
    expect($updatedNewsletter->preheader)->equals('Updated preheader');
    expect($updatedNewsletter->body)->equals('{"value":"Updated body"}');
    expect($updatedNewsletter->senderName)->equals('Updated sender name');
    expect($updatedNewsletter->senderAddress)->equals('Updated sender address');
    expect($updatedNewsletter->replyToName)->equals('Updated reply-to name');
    expect($updatedNewsletter->replyToAddress)->equals('Updated reply-to address');
    expect($updatedNewsletter->gaCampaign)->equals('Updated GA campaign');
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $endpoint = $this->createNewslettersEndpointWithMocks([
      'cronHelper' => $this->cronHelper,
      'subscribersFeature' => Stub::make(SubscribersFeature::class, ['check' => true]),
    ]);
    $res = $endpoint->setStatus([
      'id' => $this->newsletter->id,
      'status' => Newsletter::STATUS_ACTIVE,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItCanSetANewsletterStatus() {
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

  public function testItReschedulesPastDuePostNotificationsWhenStatusIsSetBackToActive() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();
    $schedule = sprintf('0 %d * * *', Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->hour); // every day at current hour
    $randomFutureDate = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addDays(10)->format('Y-m-d H:i:s'); // 10 days from now
    $newsletterOption = NewsletterOption::createOrUpdate(
      [
        'newsletter_id' => $this->postNotification->id,
        'option_field_id' => $newsletterOptionField->id,
        'value' => $schedule,
      ]
    );
    $sendingQueue1 = SendingTask::create();
    $sendingQueue1->newsletterId = $this->postNotification->id;
    $sendingQueue1->scheduledAt = Scheduler::getPreviousRunDate($schedule);
    $sendingQueue1->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue1->save();
    $sendingQueue2 = SendingTask::create();
    $sendingQueue2->newsletterId = $this->postNotification->id;
    $sendingQueue2->scheduledAt = $randomFutureDate;
    $sendingQueue2->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue2->save();
    $sendingQueue3 = SendingTask::create();
    $sendingQueue3->newsletterId = $this->postNotification->id;
    $sendingQueue3->scheduledAt = Scheduler::getPreviousRunDate($schedule);
    $sendingQueue3->save();

    $this->endpoint->setStatus(
      [
        'id' => $this->postNotification->id,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $tasks = ScheduledTask::findMany();
    // previously scheduled notification is rescheduled for future date
    expect($tasks[0]->scheduled_at)->equals(Scheduler::getNextRunDate($schedule));
    // future scheduled notifications are left intact
    expect($tasks[1]->scheduled_at)->equals($randomFutureDate);
    // previously unscheduled (e.g., sent/sending) notifications are left intact
    expect($tasks[2]->scheduled_at)->equals(Scheduler::getPreviousRunDate($schedule));
  }

  public function testItSchedulesPostNotificationsWhenStatusIsSetBackToActive() {
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->name = 'schedule';
    $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
    $newsletterOptionField->save();
    $schedule = '* * * * *';
    NewsletterOption::createOrUpdate(
      [
        'newsletter_id' => $this->postNotification->id,
        'option_field_id' => $newsletterOptionField->id,
        'value' => $schedule,
      ]
    );

    $this->endpoint->setStatus(
      [
        'id' => $this->postNotification->id,
        'status' => Newsletter::STATUS_ACTIVE,
      ]
    );
    $tasks = ScheduledTask::findMany();
    expect($tasks)->notEmpty();
  }

  public function testItCanRestoreANewsletter() {
    $this->newsletterRepository->bulkTrash([$this->newsletter->id]);

    $trashedNewsletter = Newsletter::findOne($this->newsletter->id);
    expect($trashedNewsletter->deletedAt)->notNull();

    $response = $this->endpoint->restore(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashANewsletter() {
    $response = $this->endpoint->trash(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteANewsletter() {
    $response = $this->endpoint->delete(['id' => $this->newsletter->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateANewsletter() {
    $wp = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = $this->createNewslettersEndpointWithMocks([
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
      'subscribersFeature' => Stub::make(SubscribersFeature::class),
    ]);

    $response = $this->endpoint->duplicate(['id' => $this->newsletter->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Standard Newsletter')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);

    $hookName = 'mailpoet_api_newsletters_duplicate_after';
    expect(WPHooksHelper::isActionDone($hookName))->true();
    expect(WPHooksHelper::getActionDone($hookName)[0] instanceof Newsletter)->true();

    $response = $this->endpoint->duplicate(['id' => $this->postNotification->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Post Notification')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanCreateANewsletter() {
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

  public function testItCanGetListingData() {
    $segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment1->id,
      ]
    );
    $newsletterSegment->save();

    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment2->id,
      ]
    );
    $newsletterSegment->save();

    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate(
      [
        'newsletter_id' => $this->postNotification->id,
        'segment_id' => $segment2->id,
      ]
    );
    $newsletterSegment->save();

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
      ->equals($segment1->id);
    expect($response->data[0]['segments'][1]['id'])
      ->equals($segment2->id);

    // 2nd subscriber has 1 segment
    expect($response->data[1]['segments'])->count(1);
    expect($response->data[1]['segments'][0]['id'])
      ->equals($segment2->id);
  }

  public function testItCanFilterListing() {
    // create 2 segments
    $segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    // link standard newsletter to the 2 segments
    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate(
      [
        'newsletter_id' => $this->newsletter->id,
        'segment_id' => $segment1->id,
      ]
    );
    $newsletterSegment->save();

    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate
    ([
       'newsletter_id' => $this->newsletter->id,
       'segment_id' => $segment2->id,
     ]
    );
    $newsletterSegment->save();

    // link post notification to the 2nd segment
    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->hydrate(
      [
        'newsletter_id' => $this->postNotification->id,
        'segment_id' => $segment2->id,
      ]
    );
    $newsletterSegment->save();

    // filter by 1st segment
    $response = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => $segment1->id,
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
          'segment' => $segment2->id,
        ],
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);

    // we should have the 2 newsletters
    expect($response->meta['count'])->equals(2);
  }

  public function testItCanLimitListing() {
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
      $this->postNotification->subject
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

  public function testItCanBulkDeleteSelectionOfNewsletters() {
    $selectionIds = [
      $this->newsletter->id,
      $this->postNotification->id,
    ];

    $response = $this->endpoint->bulkAction(
      [
        'listing' => [
          'selection' => $selectionIds,
        ],
        'action' => 'delete',
      ]
    );

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(count($selectionIds));
  }

  public function testItCanBulkDeleteNewsletters() {
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

  public function testItCanSendAPreview() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->createNewslettersEndpointWithMocks([
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => null,
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->id,
    ];
    $response = $endpoint->sendPreview($data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItReturnsMailerErrorWhenSendingFailed() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->createNewslettersEndpointWithMocks([
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => Expected::once(function () {
          throw new SendPreviewException('The email could not be sent: failed');
        }),
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->id,
    ];
    $response = $endpoint->sendPreview($data);
    expect($response->errors[0]['message'])->equals('The email could not be sent: failed');
  }

  public function testItReturnsBrowserPreviewUrlWithoutProtocol() {
    $data = [
      'id' => $this->newsletter->id,
      'body' => 'fake body',
    ];

    $emoji = $this->make(
      Emoji::class,
      ['encodeForUTF8Column' => Expected::once(function ($params) {
        return $params;
      })]
    );

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = $this->createNewslettersEndpointWithMocks([
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
      'emoji' => $emoji,
      'subscribersFeature' => Stub::make(SubscribersFeature::class),
    ]);

    $response = $this->endpoint->showPreview($data);
    expect($response->meta['preview_url'])->stringNotContainsString('http');
    expect($response->meta['preview_url'])->regExp('!^\/\/!');
  }

  public function testItGeneratesPreviewLinksWithNewsletterHashAndNoSubscriberData() {
    $response = $this->endpoint->listing();
    $previewLink = $response->data[0]['preview_url'];
    parse_str((string)parse_url($previewLink, PHP_URL_QUERY), $previewLinkData);
    $previewLinkData = Url::transformUrlDataObject(Router::decodeRequestData($previewLinkData['data']));
    expect($previewLinkData['newsletter_hash'])->notEmpty();
    expect($previewLinkData['subscriber_id'])->false();
    expect($previewLinkData['subscriber_token'])->false();
    expect((boolean)$previewLinkData['preview'])->true();
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }

  private function createNewslettersEndpointWithMocks(array $mocks): Newsletters {
    return new Newsletters(
      $this->diContainer->get(Handler::class),
      $mocks['wp'] ?? $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SettingsController::class),
      $mocks['cronHelper'] ?? $this->diContainer->get(CronHelper::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(NewsletterListingRepository::class),
      $this->diContainer->get(NewslettersResponseBuilder::class),
      $this->diContainer->get(PostNotificationScheduler::class),
      $mocks['emoji'] ?? $this->diContainer->get(Emoji::class),
      $mocks['subscribersFeature'] ?? $this->diContainer->get(SubscribersFeature::class),
      $mocks['sendPreviewController'] ?? $this->diContainer->get(SendPreviewController::class),
      $this->diContainer->get(NewsletterSaveController::class)
    );
  }
}
