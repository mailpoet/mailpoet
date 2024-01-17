<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\JSON\v1\Newsletters;
use MailPoet\Cron\CronHelper;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewslettersTest extends \MailPoetTest {

  /** @var NewsletterEntity */
  public $postNotification;

  /** @var NewsletterEntity */
  public $newsletter;

  /** @var Newsletters */
  private $endpoint;

  /** @var CronHelper */
  private $cronHelper;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var Url */
  private $newsletterUrl;

  /** @var Scheduler */
  private $scheduler;

  public function _before() {
    parent::_before();
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->newsletterRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->segmentRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->newsletterSegmentRepository = ContainerWrapper::getInstance()->get(NewsletterSegmentRepository::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->newslettersResponseBuilder = ContainerWrapper::getInstance()->get(NewslettersResponseBuilder::class);
    $this->newsletterUrl = ContainerWrapper::getInstance()->get(Url::class);
    $this->scheduler = ContainerWrapper::getInstance()->get(Scheduler::class);
    $this->endpoint = Stub::copy(
      ContainerWrapper::getInstance()->get(Newsletters::class),
      [
        'newslettersResponseBuilder' => new NewslettersResponseBuilder(
          $this->diContainer->get(EntityManager::class),
          $this->diContainer->get(NewslettersRepository::class),
          new NewsletterStatisticsRepository(
            $this->diContainer->get(EntityManager::class),
            $this->makeEmpty(WCHelper::class)
          ),
          $this->diContainer->get(Url::class),
          $this->diContainer->get(SendingQueuesRepository::class),
          $this->diContainer->get(LogRepository::class)
        ),
      ]
    );
    $this->newsletter = (new Newsletter())->withDefaultBody()->withSubject('My Standard Newsletter')->create();
    $this->postNotification = (new Newsletter())->withPostNotificationsType()->withSubject('My Post Notification')->loadBodyFrom('newsletterWithALC.json')->create();
  }

  public function testItKeepsUnsentNewslettersAtTheTopWhenSortingBySentAtDate() {
    /** @var NewsletterEntity[] */
    $sentNewsletters = [];
    for ($i = 1; $i <= 3; $i++) {
      $sentAt = Carbon::createFromFormat('Y-m-d H:i:s', "2017-01-0{$i} 01:01:01");
      if (!$sentAt) {
        continue;
      }
      $sentNewsletters[$i] = (new Newsletter())->withSubject("Sent newsletter {$i}")->create();
      $sentNewsletters[$i]->setSentAt($sentAt);
    }
    $this->newsletterRepository->flush();

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
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data[0]['id'])->equals($this->newsletter->getId());
    verify($response->data[1]['id'])->equals($sentNewsletters[1]->getId());
    verify($response->data[2]['id'])->equals($sentNewsletters[2]->getId());
    verify($response->data[3]['id'])->equals($sentNewsletters[3]->getId());

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
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data[0]['id'])->equals($this->newsletter->getId());
    verify($response->data[1]['id'])->equals($sentNewsletters[3]->getId());
    verify($response->data[2]['id'])->equals($sentNewsletters[2]->getId());
    verify($response->data[3]['id'])->equals($sentNewsletters[1]->getId());
  }

  public function testItCanGetANewsletter() {
    $response = $this->endpoint->get(); // missing id
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This email does not exist.');

    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $this->endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
    ]);
    $response = $this->endpoint->get(['id' => $this->newsletter->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
      NewslettersResponseBuilder::RELATION_OPTIONS,
      NewslettersResponseBuilder::RELATION_QUEUE,
    ]));
    $hookName = 'mailpoet_api_newsletters_get_after';
    verify(WPHooksHelper::isFilterApplied($hookName))->true();
    verify(WPHooksHelper::getFilterApplied($hookName)[0])->isArray();
  }

  public function testItCanSaveANewsletter() {
    $newsletterData = [
      'id' => $this->newsletter->getId(),
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
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $updatedNewsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter); // PHPStan
    verify($response->data)->equals($this->newslettersResponseBuilder->build($updatedNewsletter, [NewslettersResponseBuilder::RELATION_SEGMENTS]));
    verify($updatedNewsletter->getType())->equals('Updated type');
    verify($updatedNewsletter->getSubject())->equals('Updated subject');
    verify($updatedNewsletter->getPreheader())->equals('Updated preheader');
    verify($updatedNewsletter->getBody())->equals(['value' => 'Updated body']);
    verify($updatedNewsletter->getSenderName())->equals('Updated sender name');
    verify($updatedNewsletter->getSenderAddress())->equals('Updated sender address');
    verify($updatedNewsletter->getReplyToName())->equals('Updated reply-to name');
    verify($updatedNewsletter->getReplyToAddress())->equals('Updated reply-to address');
    verify($updatedNewsletter->getGaCampaign())->equals('Updated GA campaign');
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'cronHelper' => $this->cronHelper,
      'subscribersFeature' => Stub::make(Subscribers::class, ['check' => true]),
    ]);
    $res = $endpoint->setStatus([
      'id' => $this->newsletter->getId(),
      'status' => NewsletterEntity::STATUS_ACTIVE,
    ]);
    verify($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReturnsErrorIfSenderAddressNotValidForActivation() {
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'cronHelper' => $this->cronHelper,
      'subscribersFeature' => Stub::make(Subscribers::class, ['check' => true]),
      'authorizedEmailsController' => Stub::make(AuthorizedEmailsController::class, [
        'isSenderAddressValid' => Expected::once(false),
      ]),
    ]);
    $res = $endpoint->setStatus([
      'id' => $this->postNotification->getId(),
      'status' => NewsletterEntity::STATUS_ACTIVE,
    ]);
    verify($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItCanSetANewsletterStatus() {
    // set status to sending
    $response = $this->endpoint->setStatus
    ([
       'id' => $this->newsletter->getId(),
       'status' => NewsletterEntity::STATUS_SENDING,
     ]
    );
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['status'])->equals(NewsletterEntity::STATUS_SENDING);

    // set status to draft
    $response = $this->endpoint->setStatus(
      [
        'id' => $this->newsletter->getId(),
        'status' => NewsletterEntity::STATUS_DRAFT,
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['status'])->equals(NewsletterEntity::STATUS_DRAFT);

    // no status specified throws an error
    $response = $this->endpoint->setStatus(
      [
        'id' => $this->newsletter->getId(),
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])
      ->equals('You need to specify a status.');

    // invalid newsletter id throws an error
    $response = $this->endpoint->setStatus(
      [
        'status' => NewsletterEntity::STATUS_DRAFT,
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This email does not exist.');
  }

  public function testItReschedulesPastDuePostNotificationsWhenStatusIsSetBackToActive() {
    $schedule = sprintf('0 %d * * *', Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->hour); // every day at current hour
    $randomFutureDate = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addDays(10)->format('Y-m-d H:i:s'); // 10 days from now
    (new NewsletterOption())->create($this->postNotification, NewsletterOptionFieldEntity::NAME_SCHEDULE, $schedule);

    $sendingQueue1 = SendingTask::create();
    $sendingQueue1->newsletterId = $this->postNotification->getId();
    $sendingQueue1->scheduledAt = $this->scheduler->getPreviousRunDate($schedule);
    $sendingQueue1->status = SendingQueueEntity::STATUS_SCHEDULED;
    $sendingQueue1->save();
    $sendingQueue2 = SendingTask::create();
    $sendingQueue2->newsletterId = $this->postNotification->getId();
    $sendingQueue2->scheduledAt = $randomFutureDate;
    $sendingQueue2->status = SendingQueueEntity::STATUS_SCHEDULED;
    $sendingQueue2->save();
    $sendingQueue3 = SendingTask::create();
    $sendingQueue3->newsletterId = $this->postNotification->getId();
    $sendingQueue3->scheduledAt = $this->scheduler->getPreviousRunDate($schedule);
    $sendingQueue3->save();

    $this->entityManager->clear();
    $this->endpoint->setStatus(
      [
        'id' => $this->postNotification->getId(),
        'status' => NewsletterEntity::STATUS_ACTIVE,
      ]
    );
    $tasks = $this->scheduledTasksRepository->findAll();
    // previously scheduled notification is rescheduled for future date
    $this->assertInstanceOf(\DateTimeInterface::class, $tasks[0]->getScheduledAt());
    verify($tasks[0]->getScheduledAt()->format('Y-m-d H:i:s'))->equals($this->scheduler->getNextRunDate($schedule));
    // future scheduled notifications are left intact
    $this->assertInstanceOf(\DateTimeInterface::class, $tasks[1]->getScheduledAt());
    verify($tasks[1]->getScheduledAt()->format('Y-m-d H:i:s'))->equals($randomFutureDate);
    // previously unscheduled (e.g., sent/sending) notifications are left intact
    $this->assertInstanceOf(\DateTimeInterface::class, $tasks[2]->getScheduledAt());
    verify($tasks[2]->getScheduledAt()->format('Y-m-d H:i:s'))->equals($this->scheduler->getPreviousRunDate($schedule));
  }

  public function testItSchedulesPostNotificationsWhenStatusIsSetBackToActive() {
    $schedule = '* * * * *';
    (new NewsletterOption())->create($this->postNotification, NewsletterOptionFieldEntity::NAME_SCHEDULE, $schedule);

    $this->endpoint->setStatus(
      [
        'id' => $this->postNotification->getId(),
        'status' => NewsletterEntity::STATUS_ACTIVE,
      ]
    );
    $tasks = $this->scheduledTasksRepository->findAll();
    verify($tasks)->notEmpty();
  }

  public function testItCanRestoreANewsletter() {
    $this->newsletterRepository->bulkTrash([$this->newsletter->getId()]);
    $this->entityManager->clear();

    $trashedNewsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $trashedNewsletter);
    verify($trashedNewsletter->getDeletedAt())->notNull();

    $response = $this->endpoint->restore(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
    verify($response->data['deleted_at'])->null();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanTrashANewsletter() {
    $response = $this->endpoint->trash(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
    verify($response->data['deleted_at'])->notNull();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteANewsletter() {
    $response = $this->endpoint->delete(['id' => $this->newsletter->getId()]);
    verify($response->data)->empty();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateANewsletter() {
    $wp = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);
    $this->endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
    ]);

    $response = $this->endpoint->duplicate(['id' => $this->newsletter->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletterCopy = $this->newsletterRepository->findOneBy(['subject' => 'Copy of My Standard Newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterCopy);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletterCopy));
    verify($response->meta['count'])->equals(1);

    $hookName = 'mailpoet_api_newsletters_duplicate_after';
    verify(WPHooksHelper::isActionDone($hookName))->true();
    verify(WPHooksHelper::getActionDone($hookName)[0] instanceof NewsletterEntity)->true();

    $response = $this->endpoint->duplicate(['id' => $this->postNotification->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletterCopy = $this->newsletterRepository->findOneBy(['subject' => 'Copy of My Post Notification']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterCopy);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletterCopy));
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanCreateANewsletter() {
    $data = [
      'subject' => 'My New Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];
    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneBy(['subject' => 'My New Newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));

    $response = $this->endpoint->create();
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals('Please specify a type.');
  }

  public function testItCanCreateAnAutomationNewsletter() {
    $data = [
      'subject' => 'My Automation newsletter',
      'type' => NewsletterEntity::TYPE_AUTOMATION,
    ];
    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $newsletter = $this->newsletterRepository->findOneBy(['subject' => 'My Automation newsletter']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($response->data)->equals($this->newslettersResponseBuilder->build($newsletter));
  }

  public function testItHasDefaultSenderAfterCreate() {
    $data = [
      'subject' => 'My First Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];

    $settingsController = $this->diContainer->get(SettingsController::class);
    $settingsController->set('sender', ['name' => 'Sender', 'address' => 'sender@test.com']);
    $settingsController->set('reply_to', ['name' => 'Reply', 'address' => 'reply@test.com']);

    $response = $this->endpoint->create($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['subject'])->equals('My First Newsletter');
    verify($response->data['type'])->equals(NewsletterEntity::TYPE_STANDARD);
    verify($response->data['sender_address'])->equals('sender@test.com');
    verify($response->data['sender_name'])->equals('Sender');
    verify($response->data['reply_to_address'])->equals('reply@test.com');
    verify($response->data['reply_to_name'])->equals('Reply');
  }

  public function testItCanGetListingData() {
    $segment1 = $this->segmentRepository->createOrUpdate('Segment 1');
    $segment2 = $this->segmentRepository->createOrUpdate('Segment 2');

    $this->createNewsletterSegment($this->newsletter, $segment1);
    $this->createNewsletterSegment($this->newsletter, $segment2);
    $this->createNewsletterSegment($this->postNotification, $segment2);
    $this->entityManager->clear();

    $response = $this->endpoint->listing();

    verify($response->status)->equals(APIResponse::STATUS_OK);

    verify($response->meta)->arrayHasKey('filters');
    verify($response->meta)->arrayHasKey('groups');
    verify($response->meta['count'])->equals(2);

    verify($response->data)->arrayCount(2);
    verify($response->data[0]['subject'])->equals('My Standard Newsletter');
    verify($response->data[1]['subject'])->equals('My Post Notification');

    // 1st subscriber has 2 segments
    verify($response->data[0]['segments'])->arrayCount(2);
    verify($response->data[0]['segments'][0]['id'])
      ->equals($segment1->getId());
    verify($response->data[0]['segments'][1]['id'])
      ->equals($segment2->getId());

    // 2nd subscriber has 1 segment
    verify($response->data[1]['segments'])->arrayCount(1);
    verify($response->data[1]['segments'][0]['id'])
      ->equals($segment2->getId());
  }

  public function testItCanFilterListing() {
    // create 2 segments
    $segment1 = $this->segmentRepository->createOrUpdate('Segment 1');
    $segment2 = $this->segmentRepository->createOrUpdate('Segment 2');

    // link standard newsletter to the 2 segments
    $this->createNewsletterSegment($this->newsletter, $segment1);
    $this->createNewsletterSegment($this->newsletter, $segment2);

    // link post notification to the 2nd segment
    $this->createNewsletterSegment($this->postNotification, $segment2);

    // filter by 1st segment
    $response = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => $segment1->getId(),
        ],
      ]
    );

    verify($response->status)->equals(APIResponse::STATUS_OK);

    // we should only get the standard newsletter
    verify($response->meta['count'])->equals(1);
    verify($response->data[0]['subject'])->equals($this->newsletter->getSubject());

    // filter by 2nd segment
    $response = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => $segment2->getId(),
        ],
      ]
    );

    verify($response->status)->equals(APIResponse::STATUS_OK);

    // we should have the 2 newsletters
    verify($response->meta['count'])->equals(2);
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

    verify($response->status)->equals(APIResponse::STATUS_OK);

    verify($response->meta['count'])->equals(2);
    verify($response->data)->arrayCount(1);
    verify($response->data[0]['subject'])->equals(
      $this->postNotification->getSubject()
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

    verify($response->meta['count'])->equals(2);
    verify($response->data)->arrayCount(1);
    verify($response->data[0]['subject'])->equals(
      $this->newsletter->getSubject()
    );
  }

  public function testItCanBulkDeleteSelectionOfNewsletters() {
    $selectionIds = [
      $this->newsletter->getId(),
      $this->postNotification->getId(),
    ];

    $response = $this->endpoint->bulkAction(
      [
        'listing' => [
          'selection' => $selectionIds,
        ],
        'action' => 'delete',
      ]
    );

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(count($selectionIds));
  }

  public function testItCanBulkDeleteNewsletters() {
    $response = $this->endpoint->bulkAction(
      [
        'action' => 'trash',
        'listing' => ['group' => 'all'],
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction(
      [
        'action' => 'delete',
        'listing' => ['group' => 'trash'],
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction(
      [
        'action' => 'delete',
        'listing' => ['group' => 'trash'],
      ]
    );
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(0);
  }

  public function testItCanSendAPreview() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => null,
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->getId(),
    ];
    $response = $endpoint->sendPreview($data);
    verify($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItReturnsMailerErrorWhenSendingFailed() {
    $subscriber = 'test@subscriber.com';
    $endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'sendPreviewController' => $this->make(SendPreviewController::class, [
        'sendPreview' => Expected::once(function () {
          throw new SendPreviewException('The email could not be sent: failed');
        }),
      ]),
    ]);

    $data = [
      'subscriber' => $subscriber,
      'id' => $this->newsletter->getId(),
    ];
    $response = $endpoint->sendPreview($data);
    verify($response->errors[0]['message'])->equals('The email could not be sent: failed');
  }

  public function testItReturnsBrowserPreviewUrlWithoutProtocol() {
    $data = [
      'id' => $this->newsletter->getId(),
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
    $this->endpoint = $this->getServiceWithOverrides(Newsletters::class, [
      'wp' => $wp,
      'cronHelper' => $this->cronHelper,
      'emoji' => $emoji,
    ]);

    $response = $this->endpoint->showPreview($data);
    verify($response->meta['preview_url'])->stringNotContainsString('http');
    verify($response->meta['preview_url'])->stringMatchesRegExp('!^\/\/!');
  }

  public function testItGeneratesPreviewLinksWithNewsletterHashAndNoSubscriberData() {
    $response = $this->endpoint->listing();
    $previewLink = $response->data[0]['preview_url'];
    parse_str((string)parse_url($previewLink, PHP_URL_QUERY), $previewLinkData);
    $previewLinkData = $this->newsletterUrl->transformUrlDataObject(Router::decodeRequestData($previewLinkData['data']));
    verify($previewLinkData['newsletter_hash'])->notEmpty();
    verify($previewLinkData['subscriber_id'])->false();
    verify($previewLinkData['subscriber_token'])->false();
    verify((boolean)$previewLinkData['preview'])->true();
  }

  private function createNewsletterSegment(
    NewsletterEntity $newsletter,
    SegmentEntity $segment
  ): NewsletterSegmentEntity {
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->newsletterSegmentRepository->persist($newsletterSegment);
    $this->newsletterSegmentRepository->flush();
    return $newsletterSegment;
  }
}
