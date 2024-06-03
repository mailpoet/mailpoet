<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SendingQueueTest extends \MailPoetTest {
  /** @var NewsletterEntity */
  private $newsletter;

  /** @var NewsletterOption */
  private $newsletterOptionsFactory;

  public function _before() {
    parent::_before();
    $this->newsletterOptionsFactory = new NewsletterOption();

    $this->newsletter = (new NewsletterFactory())
      ->withSubject('My Standard Newsletter')
      ->withDefaultBody()
      ->create();

    $settings = SettingsController::getInstance();
    $settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCreatesNewScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $newsletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);

    $sendingQueueApi = $this->diContainer->get(SendingQueueAPI::class);
    $result = $sendingQueueApi->add(['newsletter_id' => $newsletter->getId()]);
    $sendingQueue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    verify($scheduledTask->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $scheduled = $scheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    verify($scheduled->format('Y-m-d H:i:s'))->equals($newsletterOptions['scheduledAt']);
    verify($scheduledTask->getType())->equals(SendingQueue::TASK_TYPE);

    $this->assertSame($sendingQueue->getId(), $result->data['id']);
    $this->assertSame(SendingQueue::TASK_TYPE, $result->data['type']);
    $this->assertSame(ScheduledTaskEntity::STATUS_SCHEDULED, $result->data['status']);
    $this->assertSame(5, $result->data['priority']);
    $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result->data['scheduled_at']);
    $this->assertNull($result->data['processed_at']);
    $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result->data['created_at']);
    $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result->data['updated_at']);
    $this->assertNull($result->data['deleted_at']);
    $this->assertNull($result->data['in_progress']);
    $this->assertSame(0, $result->data['reschedule_count']);
    $this->assertNull($result->data['meta']);
    $this->assertSame($scheduledTask->getId(), $result->data['task_id']);
    $this->assertSame($newsletter->getId(), $result->data['newsletter_id']);
    $this->assertNull($result->data['newsletter_rendered_body']);
    $this->assertNull($result->data['newsletter_rendered_subject']);
    $this->assertSame(0, $result->data['count_total']);
    $this->assertSame(0, $result->data['count_processed']);
    $this->assertSame(0, $result->data['count_to_process']);
    $this->assertSame(200, $result->status);
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'subscribersFeature' => Stub::make(SubscribersFeature::class, [
        'check' => true,
      ]),
    ]);
    $res = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()]);
    verify($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
    $res = $sendingQueue->resume(['newsletter_id' => $this->newsletter->getId()]);
    verify($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReschedulesScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $newsletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);
    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);

    // add scheduled task
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTask = $repo->findOneById($result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $scheduled = $scheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    verify($scheduled->format('Y-m-d H:i:s'))->equals('2018-10-10 10:00:00');

    // update scheduled time
    $newsletterOptions = [
      'scheduledAt' => '2018-11-11 11:00:00',
    ];
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, $newsletterOptions);
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->getId()]);
    $repo = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->entityManager->clear();
    $rescheduledTask = $repo->findOneById($result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $rescheduledTask);
    // new task was not created
    verify($rescheduledTask->getId())->equals($scheduledTask->getId());
    // scheduled time was updated
    $scheduled = $rescheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    verify($scheduled->format('Y-m-d H:i:s'))->equals('2018-11-11 11:00:00');
  }

  public function testAddReturnsErrorIfThereAreNoSubscribersAssociatedWithTheNewsletter() {
    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);
    $expectedResult = [
      'errors' => [
        [
          'error' => 'unknown',
          'message' => 'There are no subscribers in that list!',
        ],
      ],
    ];

    $data = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()])->getData();
    $this->assertSame($expectedResult, $data);
  }

  public function testAddChangesNewsletterStatus() {
    $sendingQueueApi = $this->diContainer->get(SendingQueueAPI::class);

    $segment = (new SegmentFactory())->create();
    $subscriber = (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())
      ->withSegments([$segment])
      ->withSubscriber($subscriber)
      ->create();

    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $newsletter->getStatus());

    $result = $sendingQueueApi->add(['newsletter_id' => $newsletter->getId()]);
    $sendingQueue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
    $this->assertSame(1, $sendingQueue->getCountTotal());
    $this->assertSame(0, $sendingQueue->getCountProcessed());
    $this->assertSame(1, $sendingQueue->getCountToProcess());
    $this->assertNull($scheduledTask->getStatus());
    $this->assertNull($scheduledTask->getScheduledAt());

    $this->assertSame($sendingQueue->getId(), $result->data['id']);
    $this->assertSame(SendingQueue::TASK_TYPE, $result->data['type']);
    $this->assertNull($result->data['status']);
    $this->assertSame(5, $result->data['priority']);
    $this->assertNull($result->data['scheduled_at']);
    $this->assertNull($result->data['processed_at']);
    $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result->data['created_at']);
    $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result->data['updated_at']);
    $this->assertNull($result->data['deleted_at']);
    $this->assertNull($result->data['in_progress']);
    $this->assertSame(0, $result->data['reschedule_count']);
    $this->assertNull($result->data['meta']);
    $this->assertSame($scheduledTask->getId(), $result->data['task_id']);
    $this->assertSame($newsletter->getId(), $result->data['newsletter_id']);
    $this->assertNull($result->data['newsletter_rendered_body']);
    $this->assertNull($result->data['newsletter_rendered_subject']);
    $this->assertSame(1, $result->data['count_total']);
    $this->assertSame(0, $result->data['count_processed']);
    $this->assertSame(1, $result->data['count_to_process']);
    $this->assertSame(200, $result->status);
  }

  public function testItRejectsInvalidNewsletters() {
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'newsletterValidator' => Stub::make(NewsletterValidator::class, ['validate' => 'some error']),
    ]);
    $response = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()]);
    $response = $response->getData();
    verify($response['errors'][0])->isArray();
    verify($response['errors'][0]['message'])->stringContainsString('some error');
    verify($response['errors'][0]['error'])->stringContainsString('bad_request');
  }

  public function testAddReturnsErrorIfNewsletterIsAlreadyBeingSent() {
    $scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null);
    (new SendingQueueFactory())->create($scheduledTask, $this->newsletter);

    $expectedResult = [
      'errors' => [
        [
          'error' => 'not_found',
          'message' => 'This newsletter is already being sent.',
        ],
      ],
    ];

    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);

    $data = $sendingQueue->add(['newsletter_id' => $this->newsletter->getId()])->getData();

    $this->assertSame($expectedResult, $data);
  }

  public function testItReturnsErrorIfCronPingThrowsException() {
    $errorMessage = 'some error';
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'cronHelper' => Stub::make(CronHelper::class, ['pingDaemon' => function () use ($errorMessage) {
        throw new \Exception($errorMessage);
      }]),
    ]);
    $response = $sendingQueue->pingCron();
    $response = $response->getData();
    verify($response['errors'][0])->isArray();
    verify($response['errors'][0]['message'])->stringContainsString($errorMessage);
    verify($response['errors'][0]['error'])->stringContainsString('unknown');
  }

  public function testItReturnsErrorIfCronPingResponseIsInvalid() {
    $errorResponse = 'timed out';
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'cronHelper' => Stub::make(CronHelper::class, ['pingDaemon' => $errorResponse]),
    ]);
    $response = $sendingQueue->pingCron();
    $response = $response->getData();
    verify($response['errors'][0])->isArray();
    verify($response['errors'][0]['message'])->stringContainsString($errorResponse);
    verify($response['errors'][0]['error'])->stringContainsString('unknown');
  }

  public function testItPingsCronSuccessfully() {
    $sendingQueue = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'cronHelper' => Stub::make(CronHelper::class, ['pingDaemon' => DaemonHttpRunner::PING_SUCCESS_RESPONSE]),
    ]);
    $response = $sendingQueue->pingCron();
    verify($response->status)->equals(200);
    $response = $response->getData();
    verify($response['data'])->empty();
    verify(empty($response['errors']))->true();
  }
}
