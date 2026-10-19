<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\DaemonHttpRunner;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Sending\OverdueCampaignHold;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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

  public function testResumeIsRefusedWhenSenderDomainIsNotReady(): void {
    $task = $this->createPausedCampaign('sender@brand.com');
    $calls = [];
    $api = $this->getResumeApi($calls, false);

    $result = $api->resume(['newsletter_id' => $this->newsletter->getId()]);

    verify($result->status)->equals(APIResponse::STATUS_FORBIDDEN);
    verify($result->errors[0]['error'])->equals(APIError::FORBIDDEN);
    $message = $result->errors[0]['message'];
    verify($message)->equals('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, authenticate brand.com or switch to another authenticated sender domain, then click Resume again.');
    verify($message)->stringNotContainsString('&#');
    verify($message)->stringNotContainsString('&amp;');
    verify($calls[0]['context'])->equals('resume');
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(ScheduledTaskEntity::class, $task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
    verify($reloaded->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    verify($reloaded->getPauseReason())->equals(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
  }

  public function testResumeRefusalMessageForFreeMailSender(): void {
    $this->createPausedCampaign('sender@gmail.com');
    $calls = [];
    $result = $this->getResumeApi($calls, false)->resume(['newsletter_id' => $this->newsletter->getId()]);
    verify($result->errors[0]['message'])->equals('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, send it from an address on your own authenticated domain instead of gmail.com, then click Resume again.');
  }

  public function testResumeRefusalMessageForGenericSender(): void {
    $this->createPausedCampaign('');
    $calls = [];
    $result = $this->getResumeApi($calls, false)->resume(['newsletter_id' => $this->newsletter->getId()]);
    verify($result->errors[0]['message'])->equals('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, send it from an authenticated sender domain, then click Resume again.');
  }

  public function testResumeRefusalMessageIsPlainText(): void {
    $this->createPausedCampaign("sender@o'brien&co.com");
    $calls = [];
    $result = $this->getResumeApi($calls, false)->resume(['newsletter_id' => $this->newsletter->getId()]);
    verify($result->errors[0]['message'])->stringContainsString("authenticate o'brien&co.com or");
  }

  public function testResumeSucceedsWhenSenderIsValid(): void {
    $task = $this->createPausedCampaign('sender@brand.com');
    $calls = [];
    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $this->newsletter->getId()]);

    verify($result->status)->equals(200);
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(ScheduledTaskEntity::class, $task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
    verify($reloaded->getStatus())->null();
    verify($reloaded->getPauseReason())->null();
  }

  public function testResumeWithoutQueueReportsNotSentYetWithoutCheckingSender(): void {
    $newsletter = (new NewsletterFactory())->withSubject('No queue')->withDefaultBody()->create();
    $calls = [];
    $result = $this->getResumeApi($calls, false)->resume(['newsletter_id' => $newsletter->getId()]);
    verify($result->errors[0]['error'])->equals(APIError::UNKNOWN);
    verify($result->errors[0]['message'])->equals('This newsletter has not been sent yet.');
    verify($calls)->empty();
  }

  public function testResumePassesMarkerToValidation(): void {
    $this->createPausedCampaign('sender@brand.com', true);
    $calls = [];
    $this->getResumeApi($calls, false)->resume(['newsletter_id' => $this->newsletter->getId()]);
    verify($calls[0]['requireVerifiedDomain'])->true();

    $other = (new NewsletterFactory())->withSubject('Unmarked')->withDefaultBody()->withSenderAddress('sender@brand.com')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED])->create();
    $calls = [];
    $this->getResumeApi($calls, false)->resume(['newsletter_id' => $other->getId()]);
    verify($calls[0]['requireVerifiedDomain'])->false();
  }

  public function testResumeFindsMarkerOnAnyTimeZoneSibling(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSenderAddress('sender@brand.com')->withSegments([$segment])->create();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => '1',
      NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE => TimeZoneCampaignScheduler::SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE => (new \DateTimeImmutable('+3 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME => '12:00:00',
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => (new \DateTimeImmutable('+3 days', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
    ]);
    $scheduler = $this->getServiceWithOverrides(TimeZoneCampaignScheduler::class, [
      'capabilitiesManager' => Stub::makeEmpty(CapabilitiesManager::class, [
        'getCapability' => new Capability('sendByTimezone', Capability::TYPE_BOOLEAN, false),
      ]),
      'featuresController' => Stub::make(FeaturesController::class, ['isSupported' => true]),
      'wp' => Stub::make(WPFunctions::class, ['wpTimezone' => new \DateTimeZone('Europe/Bratislava')]),
    ]);
    $scheduler->schedule($newsletter);
    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    $this->assertGreaterThan(1, count($queues));
    $this->entityManager->refresh($newsletter);
    $scheduler->pauseCampaign($queues[0]);

    $last = $newsletter->getLastUpdatedQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $last);
    $marked = false;
    foreach ($queues as $queue) {
      if ($queue->getId() === $last->getId()) {
        continue;
      }
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
      $marked = true;
      break;
    }
    $this->assertTrue($marked);
    $this->entityManager->flush();
    $lastTask = $last->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $lastTask);
    verify($lastTask->getPauseReason())->null();

    $calls = [];
    $api = $this->getResumeApi($calls, false, [
      'timeZoneCampaignScheduler' => Stub::make(TimeZoneCampaignScheduler::class, [
        'isTimeZoneQueue' => true,
        'getCampaignQueues' => $queues,
        'resumeCampaign' => Expected::never(),
      ]),
    ]);
    $result = $api->resume(['newsletter_id' => $newsletter->getId()]);

    verify($result->status)->equals(APIResponse::STATUS_FORBIDDEN);
    verify($calls[0]['requireVerifiedDomain'])->true();
  }

  public function testResumeSyncsParentSenderToMarkedNotificationHistory(): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->withSenderAddress('old@example.com')->withSenderName('Old')->create();
    $history = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)
      ->withSenderAddress('old@example.com')->withSenderName('Old')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED])->create();
    $queue = $history->getLastUpdatedQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $parent->setSenderAddress('new@example.com');
    $parent->setSenderName('New');
    $this->entityManager->flush();

    $calls = [];
    $this->getResumeApi($calls, false)->resume(['newsletter_id' => $history->getId()]);

    verify($calls[0]['sender'])->equals('new@example.com');
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $history->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    verify($reloaded->getSenderAddress())->equals('new@example.com');
    verify($reloaded->getSenderName())->equals('New');
  }

  public function testResumeDoesNotSyncParentSenderToUnmarkedNotificationHistory(): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->withSenderAddress('old@example.com')->withSenderName('Old')->create();
    $history = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)
      ->withSenderAddress('old@example.com')->withSenderName('Old')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED])->create();
    $parent->setSenderAddress('new@example.com');
    $parent->setSenderName('New');
    $this->entityManager->flush();

    $calls = [];
    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $history->getId()]);

    verify($result->status)->equals(200);
    verify($calls)->arrayCount(1);
    verify($calls[0]['sender'])->equals('old@example.com');
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $history->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    verify($reloaded->getSenderAddress())->equals('old@example.com');
    verify($reloaded->getSenderName())->equals('Old');
  }

  public function testResumeSyncsParentSenderToUnmarkedNotificationHistoryWithInvalidSender(): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->withSenderAddress('old@example.com')->withSenderName('Old')->create();
    $history = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)
      ->withSenderAddress('old@example.com')->withSenderName('Old')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED])->create();
    $parent->setSenderAddress('new@example.com');
    $parent->setSenderName('New');
    $this->entityManager->flush();

    $calls = [];
    $api = $this->getResumeApi($calls, false, [
      'authorizedEmailsController' => Stub::make(AuthorizedEmailsController::class, [
        'isSenderAddressValid' => function(NewsletterEntity $newsletter, string $context = 'activation', bool $requireVerifiedDomain = false) use (&$calls): bool {
          $calls[] = ['requireVerifiedDomain' => $requireVerifiedDomain, 'sender' => $newsletter->getSenderAddress()];
          return $newsletter->getSenderAddress() === 'new@example.com';
        },
      ]),
    ]);
    $result = $api->resume(['newsletter_id' => $history->getId()]);

    verify($result->status)->equals(200);
    verify($calls)->arrayCount(2);
    verify($calls[0]['sender'])->equals('old@example.com');
    verify($calls[1]['sender'])->equals('new@example.com');
    verify($calls[1]['requireVerifiedDomain'])->false();
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $history->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    verify($reloaded->getSenderAddress())->equals('new@example.com');
    verify($reloaded->getSenderName())->equals('New');
  }

  private function createPausedCampaign(string $sender, bool $marked = true): ScheduledTaskEntity {
    $this->newsletter->setSenderAddress($sender);
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, new \DateTime());
    if ($marked) {
      $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    }
    $queue = (new SendingQueueFactory())->create($task, $this->newsletter);
    $queue->setCountTotal(1);
    $this->entityManager->flush();
    $this->entityManager->refresh($this->newsletter);
    return $task;
  }

  private function getResumeApi(array &$calls, bool $valid, array $overrides = []): SendingQueueAPI {
    return $this->getServiceWithOverrides(SendingQueueAPI::class, array_merge([
      'authorizedEmailsController' => Stub::make(AuthorizedEmailsController::class, [
        'isSenderAddressValid' => function(NewsletterEntity $newsletter, string $context = 'activation', bool $requireVerifiedDomain = false) use (&$calls, $valid): bool {
          $calls[] = [
            'context' => $context,
            'requireVerifiedDomain' => $requireVerifiedDomain,
            'sender' => $newsletter->getSenderAddress(),
          ];
          return $valid;
        },
      ]),
      'actionSchedulerDaemonTriggerAction' => Stub::makeEmpty(DaemonTrigger::class),
    ], $overrides));
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

  public function testItReplacesTimezoneQueuesWhenSchedulingByWebsiteTime(): void {
    $newsletter = $this->newsletter;
    $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => 1,
      NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE => TimeZoneCampaignScheduler::SCHEDULE_MODE_WEBSITE_TIME,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => '2030-10-10 10:00:00',
    ]);
    $campaignId = 'timezone-campaign';
    $oldTask1 = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new \DateTimeImmutable('2030-10-09 10:00:00')
    );
    $oldTask2 = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new \DateTimeImmutable('2030-10-09 11:00:00')
    );
    $oldQueue1 = (new SendingQueueFactory())->create($oldTask1, $newsletter);
    $oldQueue2 = (new SendingQueueFactory())->create($oldTask2, $newsletter);
    foreach ([$oldQueue1, $oldQueue2] as $oldQueue) {
      $oldQueue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
      ]);
    }
    $this->entityManager->flush();

    $result = $this->diContainer->get(SendingQueueAPI::class)->add(['newsletter_id' => $newsletter->getId()]);

    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    verify($result->status)->equals(APIResponse::STATUS_OK);
    verify($sendingQueuesRepository->findOneById((int)$oldQueue1->getId()))->null();
    verify($sendingQueuesRepository->findOneById((int)$oldQueue2->getId()))->null();
    verify($scheduledTasksRepository->findOneById((int)$oldTask1->getId()))->null();
    verify($scheduledTasksRepository->findOneById((int)$oldTask2->getId()))->null();
    $newTask = $scheduledTasksRepository->findOneById((int)$result->data['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newTask);
    $scheduled = $newTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduled);
    verify($scheduled->format('Y-m-d H:i:s'))->equals('2030-10-10 10:00:00');
  }

  public function testItReplacesOrphanedTimezoneQueuesWhenSendingImmediately(): void {
    $segment = (new SegmentFactory())->create();
    $subscriber = (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())
      ->withSegments([$segment])
      ->withSubscriber($subscriber)
      ->create();

    $campaignId = 'orphan-timezone-campaign';
    $oldTask1 = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new \DateTimeImmutable('2030-10-09 10:00:00')
    );
    $oldTask2 = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new \DateTimeImmutable('2030-10-09 11:00:00')
    );
    $oldQueue1 = (new SendingQueueFactory())->create($oldTask1, $newsletter);
    $oldQueue2 = (new SendingQueueFactory())->create($oldTask2, $newsletter);
    foreach ([$oldQueue1, $oldQueue2] as $oldQueue) {
      $oldQueue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
      ]);
    }
    $this->entityManager->flush();

    $result = $this->diContainer->get(SendingQueueAPI::class)->add(['newsletter_id' => $newsletter->getId()]);

    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    verify($result->status)->equals(APIResponse::STATUS_OK);
    verify($sendingQueuesRepository->findOneById((int)$oldQueue1->getId()))->null();
    verify($sendingQueuesRepository->findOneById((int)$oldQueue2->getId()))->null();
    verify($scheduledTasksRepository->findOneById((int)$oldTask1->getId()))->null();
    verify($scheduledTasksRepository->findOneById((int)$oldTask2->getId()))->null();

    $remainingQueues = $sendingQueuesRepository->findBy(['newsletter' => $newsletter]);
    verify(count($remainingQueues))->equals(1);
    $newQueue = $remainingQueues[0];
    verify($newQueue->getId())->equals($result->data['id']);
    verify($newQueue->getMeta())->null();
    $newTask = $newQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $newTask);
    verify($newTask->getStatus())->null();
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENDING);
  }

  public function testItRejectsImmediateSendWhenTimezoneBatchAlreadyStarted(): void {
    $segment = (new SegmentFactory())->create();
    $subscriber = (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())
      ->withSegments([$segment])
      ->withSubscriber($subscriber)
      ->create();

    $startedTask = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      new \DateTimeImmutable('2030-10-09 10:00:00')
    );
    $startedQueue = (new SendingQueueFactory())->create($startedTask, $newsletter);
    $startedQueue->setMeta([
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => 'partially-started-campaign',
    ]);
    $startedQueue->setCountProcessed(1);
    $this->entityManager->flush();

    $result = $this->diContainer->get(SendingQueueAPI::class)->add(['newsletter_id' => $newsletter->getId()]);
    verify($result->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    verify($sendingQueuesRepository->findOneById((int)$startedQueue->getId()))->notNull();
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
    $sentAt = $scheduledTask->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $sentAt);
    $this->assertEqualsWithDelta(time(), $sentAt->getTimestamp(), 10);

    $this->assertSame($sendingQueue->getId(), $result->data['id']);
    $this->assertSame(SendingQueue::TASK_TYPE, $result->data['type']);
    $this->assertNull($result->data['status']);
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
    $this->assertSame(1, $result->data['count_total']);
    $this->assertSame(0, $result->data['count_processed']);
    $this->assertSame(1, $result->data['count_to_process']);
    $this->assertSame(200, $result->status);
  }

  public function testAddOnReusedScheduledTaskStampsScheduledAt(): void {
    $segment = (new SegmentFactory())->create();
    $subscriber = (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())
      ->withSegments([$segment])
      ->withSubscriber($subscriber)
      ->create();
    $task = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::now()->subDays(3)->millisecond(0)
    );
    (new SendingQueueFactory())->create($task, $newsletter);
    $this->entityManager->refresh($newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->add(['newsletter_id' => $newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->assertSame($task->getId(), $result->data['task_id']);
    $this->assertNull($task->getStatus());
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    $this->assertEqualsWithDelta(time(), $scheduledAt->getTimestamp(), 10);
  }

  /**
   * @dataProvider heldCampaignTypes
   */
  public function testGetHeldReturnsRowForEachHeldCampaignType(string $type, bool $canReschedule): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->create();
    $factory = (new NewsletterFactory())->withSubject('Late one')->withType($type);
    if ($type === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $factory = $factory->withParent($parent);
    }
    $newsletter = $factory->create();
    $scheduledAt = Carbon::now()->subHours(30)->millisecond(0);
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null, $scheduledAt);
    (new SendingQueueFactory())->create($task, $newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->getHeld();

    $this->assertSame(200, $result->status);
    $this->assertCount(1, $result->data);
    $row = $result->data[0];
    $this->assertSame($newsletter->getId(), $row['id']);
    $this->assertSame('Late one', $row['subject']);
    $this->assertSame($type, $row['type']);
    $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $row['scheduled_at']);
    $this->assertEqualsWithDelta(30 * 3600, $row['late_seconds'], 10);
    $this->assertGreaterThan(86400, $row['late_seconds']);
    $this->assertSame($type === NewsletterEntity::TYPE_NOTIFICATION_HISTORY ? $parent->getId() : null, $row['parent_id']);
    $this->assertSame($canReschedule, $row['can_reschedule']);
    $this->assertSame(
      [
        NewsletterEntity::TYPE_STANDARD => 'admin.php?page=mailpoet-newsletters#/send/' . $newsletter->getId(),
        NewsletterEntity::TYPE_NOTIFICATION_HISTORY => 'admin.php?page=mailpoet-newsletters#/notification/history/' . $parent->getId(),
        NewsletterEntity::TYPE_RE_ENGAGEMENT => 'admin.php?page=mailpoet-newsletters#/re_engagement/' . $newsletter->getId(),
      ][$type],
      substr((string)$row['can_edit_link'], (int)strpos((string)$row['can_edit_link'], 'admin.php'))
    );
  }

  public function heldCampaignTypes(): array {
    return [
      'standard' => [NewsletterEntity::TYPE_STANDARD, true],
      'post notification issue' => [NewsletterEntity::TYPE_NOTIFICATION_HISTORY, false],
      're-engagement' => [NewsletterEntity::TYPE_RE_ENGAGEMENT, false],
    ];
  }

  public function testGetHeldReturnsNothingWhenNothingIsOverdue(): void {
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null, Carbon::now()->subHours(2));
    (new SendingQueueFactory())->create($task, $this->newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->getHeld();

    $this->assertSame([], $result->data);
    $this->assertNull($task->getStatus());
  }

  public function testResumeConfirmsAndResumesAHeldCampaign(): void {
    [$task] = $this->createHeldCampaign($this->newsletter);
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $reloaded = $this->diContainer->get(ScheduledTasksRepository::class)->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
    $this->assertNull($reloaded->getStatus());
    $this->assertNull($reloaded->getPauseReason());
    $this->assertTrue($reloaded->isOverdueSendConfirmed());
  }

  public function testResumeConfirmsEveryBatchOfAHeldTimeZoneCampaign(): void {
    $campaignId = 'held-timezone-campaign';
    $tasks = [];
    foreach ([-30, -26, 20] as $hours) {
      [$task, $queue] = $this->createHeldCampaign($this->newsletter, $hours);
      $queue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => $campaignId,
      ]);
      $tasks[] = $task;
    }
    $this->entityManager->flush();
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $repository = $this->diContainer->get(ScheduledTasksRepository::class);
    $statuses = [];
    foreach ($tasks as $task) {
      $reloaded = $repository->findOneById((int)$task->getId());
      $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
      $this->assertTrue($reloaded->isOverdueSendConfirmed());
      $this->assertNull($reloaded->getPauseReason());
      $statuses[] = $reloaded->getStatus();
    }
    $this->assertSame([null, null, ScheduledTaskEntity::STATUS_SCHEDULED], $statuses);
  }

  public function testResumeOfAUserPausedCampaignConfirmsItSoItIsNotHeldAgain(): void {
    $task = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_PAUSED,
      Carbon::now()->subHours(30)->millisecond(0)
    );
    $queue = (new SendingQueueFactory())->create($task, $this->newsletter);
    $queue->setCountTotal(1);
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->entityManager->flush();
    $this->entityManager->refresh($this->newsletter);
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->assertNull($task->getStatus());
    $this->assertTrue($task->isOverdueSendConfirmed());
    $this->assertFalse($this->diContainer->get(OverdueCampaignHold::class)->shouldHold($task, $this->newsletter));
  }

  public function testResumeActsOnTheHeldRunOfAReEngagementEmail(): void {
    $newsletter = (new NewsletterFactory())->withReengagementType()->withDefaultBody()->create();
    [$heldTask] = $this->createHeldCampaign($newsletter);
    $completedTask = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_COMPLETED,
      Carbon::now()->subDays(40)->millisecond(0)
    );
    $completedQueue = (new SendingQueueFactory())->create($completedTask, $newsletter);
    // Written with SQL because the entity listener overwrites updatedAt on flush.
    $this->entityManager->getConnection()->executeStatement(
      'UPDATE ' . $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName() . ' SET updated_at = ? WHERE id = ?',
      [Carbon::now()->addHour()->format('Y-m-d H:i:s'), $completedQueue->getId()]
    );
    $this->entityManager->refresh($completedQueue);
    $this->entityManager->refresh($newsletter);
    $lastQueue = $newsletter->getLastUpdatedQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $lastQueue);
    $this->assertSame($completedQueue->getId(), $lastQueue->getId());
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->assertNull($heldTask->getStatus());
    $this->assertTrue($heldTask->isOverdueSendConfirmed());
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $completedTask->getStatus());
    $this->assertFalse($completedTask->isOverdueSendConfirmed());
  }

  /**
   * @dataProvider activeSiblingIssues
   */
  public function testResumeOfAHeldIssueIsRefusedWhileAnotherIssueOfThePostNotificationIsActive(?string $taskStatus, bool $taskBelongsToParent, int $scheduledHoursFromNow): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->create();
    $held = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)->withSenderAddress('john.doe@example.com')->create();
    [$heldTask] = $this->createHeldCampaign($held);
    // A scheduled post notification run belongs to the parent until the Scheduler turns it into an issue.
    $owner = $taskBelongsToParent ? $parent : (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)->create();
    $activeTask = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      $taskStatus,
      Carbon::now()->addHours($scheduledHoursFromNow)->millisecond(0)
    );
    (new SendingQueueFactory())->create($activeTask, $owner);
    $this->entityManager->refresh($held);
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $held->getId()]);

    $this->assertSame(APIResponse::STATUS_BAD_REQUEST, $result->status);
    $this->assertSame(APIError::BAD_REQUEST, $result->errors[0]['error']);
    $this->assertSame('Another issue of this post notification is being sent. Wait until it finishes, then send this one.', $result->errors[0]['message']);
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $heldTask->getStatus());
    $this->assertFalse($heldTask->isOverdueSendConfirmed());
  }

  public function activeSiblingIssues(): array {
    return [
      'running issue' => [null, false, -1],
      'due run scheduled on the parent' => [ScheduledTaskEntity::STATUS_SCHEDULED, true, -1],
    ];
  }

  public function testResumeOfAHeldIssueIsAllowedWhenNoOtherIssueIsActive(): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->create();
    $held = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)->withSenderAddress('john.doe@example.com')->create();
    [$heldTask] = $this->createHeldCampaign($held);
    $futureTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addHours(5)->millisecond(0));
    (new SendingQueueFactory())->create($futureTask, $parent);
    $this->entityManager->refresh($held);
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $held->getId()]);

    $this->assertSame(200, $result->status);
    $this->assertNull($heldTask->getStatus());
    $this->assertTrue($heldTask->isOverdueSendConfirmed());
  }

  public function testCancelHeldDeletesTheQueuesAndMakesAStandardEmailADraft(): void {
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->newsletterOptionsFactory->create($this->newsletter, NewsletterOptionFieldEntity::NAME_SCHEDULED_AT, '2018-10-10 10:00:00');
    [$task, $queue] = $this->createHeldCampaign($this->newsletter);
    $taskId = (int)$task->getId();
    $queueId = (int)$queue->getId();
    $this->entityManager->refresh($this->newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $this->assertNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById($queueId));
    $this->assertNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById($taskId));
    $this->assertSame(0, $this->countTaskSubscribers($taskId));
    $newsletter = $this->entityManager->find(NewsletterEntity::class, $this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $newsletter->getStatus());
    $this->assertSame('2018-10-10 10:00:00', $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULED_AT));
  }

  public function testCancelHeldDeletesEveryBatchOfATimeZoneCampaign(): void {
    $queueIds = [];
    foreach ([-30, 20] as $hours) {
      [, $queue] = $this->createHeldCampaign($this->newsletter, $hours);
      $queue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => 'cancel-tz',
      ]);
      $queueIds[] = (int)$queue->getId();
    }
    $this->entityManager->flush();
    $this->entityManager->refresh($this->newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    foreach ($queueIds as $queueId) {
      $this->assertNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById($queueId));
    }
  }

  public function testCancelHeldWithRescheduleClearsTheStaleDateButKeepsScheduleMode(): void {
    $this->newsletterOptionsFactory->createMultipleOptions($this->newsletter, [
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => '1',
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => '2018-10-10 10:00:00',
    ]);
    $this->createHeldCampaign($this->newsletter);
    $this->entityManager->refresh($this->newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $this->newsletter->getId(), 'reschedule' => true]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $newsletter = $this->entityManager->find(NewsletterEntity::class, $this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertSame(NewsletterEntity::STATUS_DRAFT, $newsletter->getStatus());
    $this->assertEmpty($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULED_AT));
    $this->assertEquals('1', $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_IS_SCHEDULED));
  }

  public function testCancelHeldDeletesAHeldPostNotificationIssueAndKeepsItsParent(): void {
    $parent = (new NewsletterFactory())->withPostNotificationsType()->create();
    $issue = (new NewsletterFactory())->withPostNotificationHistoryType()->withParent($parent)->create();
    $this->createHeldCampaign($issue);
    $this->entityManager->refresh($issue);
    $issueId = (int)$issue->getId();
    $parentId = (int)$parent->getId();

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $issueId]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $this->assertNull($this->entityManager->find(NewsletterEntity::class, $issueId));
    $this->assertInstanceOf(NewsletterEntity::class, $this->entityManager->find(NewsletterEntity::class, $parentId));
  }

  public function testCancelHeldDeletesOnlyTheHeldRunOfAReEngagementEmail(): void {
    $newsletter = (new NewsletterFactory())->withReengagementType()->withActiveStatus()->withDefaultBody()->create();
    [$heldTask, $heldQueue] = $this->createHeldCampaign($newsletter);
    $completedTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDays(60)->millisecond(0));
    $completedQueue = (new SendingQueueFactory())->create($completedTask, $newsletter);
    $this->entityManager->refresh($newsletter);
    $ids = [(int)$heldTask->getId(), (int)$heldQueue->getId(), (int)$completedTask->getId(), (int)$completedQueue->getId(), (int)$newsletter->getId()];

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $ids[4]]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $this->assertNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById($ids[0]));
    $this->assertNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById($ids[1]));
    $this->assertSame(0, $this->countTaskSubscribers($ids[0]));
    $this->assertNotNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById($ids[2]));
    $this->assertNotNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById($ids[3]));
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $ids[4]);
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    $this->assertSame(NewsletterEntity::STATUS_ACTIVE, $reloaded->getStatus());
  }

  public function testCancelHeldRefusesAnEmailThatIsNotHeld(): void {
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED, Carbon::now()->subHours(30)->millisecond(0));
    $queue = (new SendingQueueFactory())->create($task, $this->newsletter);
    $this->entityManager->refresh($this->newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(APIResponse::STATUS_BAD_REQUEST, $result->status);
    $this->assertSame('This email is not held.', $result->errors[0]['message']);
    $this->assertNotNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById((int)$queue->getId()));
  }

  public function testCancelHeldRefusesRescheduleForAnEmailThatIsNotStandard(): void {
    $newsletter = (new NewsletterFactory())->withReengagementType()->withActiveStatus()->withDefaultBody()->create();
    [$task] = $this->createHeldCampaign($newsletter);
    $this->entityManager->refresh($newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->cancelHeld(['newsletter_id' => $newsletter->getId(), 'reschedule' => true]);

    $this->assertSame(APIResponse::STATUS_BAD_REQUEST, $result->status);
    $this->assertNotNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById((int)$task->getId()));
  }

  public function testAddDeletesTheHeldQueueInsteadOfLeavingADuplicate(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())->withSubject('Held')->withDefaultBody()->withSegments([$segment])->create();
    [$heldTask, $heldQueue] = $this->createHeldCampaign($newsletter);
    $heldQueueId = (int)$heldQueue->getId();
    $heldTaskId = (int)$heldTask->getId();
    $this->entityManager->refresh($newsletter);

    $result = $this->diContainer->get(SendingQueueAPI::class)->add(['newsletter_id' => $newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter->getId()]);
    $this->assertCount(1, $queues);
    $this->assertNotSame($heldQueueId, $queues[0]->getId());
    $this->assertNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById($heldTaskId));
  }

  public function testAddInTimeZoneModeLeavesNoHeldQueueBehind(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSenderAddress('sender@brand.com')->withSegments([$segment])->create();
    [$heldTask, $heldQueue] = $this->createHeldCampaign($newsletter);
    $heldQueueId = (int)$heldQueue->getId();
    $heldTaskId = (int)$heldTask->getId();
    $date = (new \DateTimeImmutable('+3 days', new \DateTimeZone('UTC')));
    $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => '1',
      NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE => TimeZoneCampaignScheduler::SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE => $date->format('Y-m-d'),
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME => '12:00:00',
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => $date->format('Y-m-d H:i:s'),
    ]);
    $scheduler = $this->getServiceWithOverrides(TimeZoneCampaignScheduler::class, [
      'capabilitiesManager' => Stub::makeEmpty(CapabilitiesManager::class, [
        'getCapability' => new Capability('sendByTimezone', Capability::TYPE_BOOLEAN, false),
      ]),
      'featuresController' => Stub::make(FeaturesController::class, ['isSupported' => true]),
      'wp' => Stub::make(WPFunctions::class, ['wpTimezone' => new \DateTimeZone('Europe/Bratislava')]),
    ]);
    $this->entityManager->refresh($newsletter);

    $result = $this->getServiceWithOverrides(SendingQueueAPI::class, [
      'timeZoneCampaignScheduler' => $scheduler,
      'actionSchedulerDaemonTriggerAction' => Stub::makeEmpty(DaemonTrigger::class),
    ])->add(['newsletter_id' => $newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $this->assertNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById($heldQueueId));
    $this->assertNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById($heldTaskId));
    $this->assertGreaterThan(1, count($this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter->getId()])));
  }

  /**
   * @dataProvider refusedAdds
   */
  public function testARefusedAddDeletesNothing(string $refusal): void {
    [$heldTask, $heldQueue] = $this->createHeldCampaign($this->newsletter);
    $this->entityManager->refresh($this->newsletter);
    $overrides = $refusal === 'limit'
      ? ['subscribersFeature' => Stub::make(SubscribersFeature::class, ['check' => true])]
      : ['newsletterValidator' => Stub::make(NewsletterValidator::class, ['validate' => 'Invalid email'])];

    $result = $this->getServiceWithOverrides(SendingQueueAPI::class, $overrides)->add(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertGreaterThanOrEqual(400, $result->status);
    $this->entityManager->clear();
    $this->assertNotNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById((int)$heldQueue->getId()));
    $this->assertNotNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById((int)$heldTask->getId()));
  }

  public function refusedAdds(): array {
    return [
      'subscribers limit' => ['limit'],
      'invalid newsletter' => ['invalid'],
    ];
  }

  /**
   * @dataProvider refusalsAfterTheHeldCheck
   */
  public function testAnAddRefusedLateKeepsTheHeldCopy(string $refusal): void {
    $segment = (new SegmentFactory())->create();
    if ($refusal !== 'no subscribers') {
      (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    }
    $newsletter = (new NewsletterFactory())->withSubject('Held')->withDefaultBody()->withSenderAddress('sender@brand.com')->withSegments([$segment])->create();
    [$heldTask, $heldQueue] = $this->createHeldCampaign($newsletter);
    $overrides = [];
    if ($refusal === 'already being sent') {
      $runningTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null);
      (new SendingQueueFactory())->create($runningTask, $newsletter);
    }
    if ($refusal === 'time zone batch started') {
      $startedTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDays(3)->millisecond(0));
      $startedQueue = (new SendingQueueFactory())->create($startedTask, $newsletter);
      $startedQueue->setCountProcessed(1);
      $startedQueue->setMeta([
        TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
        TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => 'started-campaign',
      ]);
      $this->entityManager->flush();
    }
    if ($refusal === 'time zone lead time') {
      $date = new \DateTimeImmutable('+2 hours', new \DateTimeZone('UTC'));
      $this->newsletterOptionsFactory->createMultipleOptions($newsletter, [
        NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => '1',
        NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE => TimeZoneCampaignScheduler::SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
        NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE => $date->format('Y-m-d'),
        NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME => '12:00:00',
        NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => $date->format('Y-m-d H:i:s'),
      ]);
      $overrides['timeZoneCampaignScheduler'] = $this->getServiceWithOverrides(TimeZoneCampaignScheduler::class, [
        'capabilitiesManager' => Stub::makeEmpty(CapabilitiesManager::class, [
          'getCapability' => new Capability('sendByTimezone', Capability::TYPE_BOOLEAN, false),
        ]),
        'featuresController' => Stub::make(FeaturesController::class, ['isSupported' => true]),
        'wp' => Stub::make(WPFunctions::class, ['wpTimezone' => new \DateTimeZone('America/New_York')]),
      ]);
    }
    $this->entityManager->refresh($newsletter);

    $result = $this->getServiceWithOverrides(SendingQueueAPI::class, array_merge([
      'actionSchedulerDaemonTriggerAction' => Stub::makeEmpty(DaemonTrigger::class),
    ], $overrides))->add(['newsletter_id' => $newsletter->getId()]);

    $this->assertGreaterThanOrEqual(400, $result->status);
    $this->entityManager->clear();
    $this->assertNotNull($this->diContainer->get(SendingQueuesRepository::class)->findOneById((int)$heldQueue->getId()));
    $this->assertNotNull($this->diContainer->get(ScheduledTasksRepository::class)->findOneById((int)$heldTask->getId()));
  }

  public function refusalsAfterTheHeldCheck(): array {
    return [
      'no subscribers' => ['no subscribers'],
      'already being sent' => ['already being sent'],
      'time zone batch started' => ['time zone batch started'],
      'time zone lead time' => ['time zone lead time'],
    ];
  }

  public function testResumeConfirmsEveryHeldQueueOfTheNewsletter(): void {
    $tasks = [];
    foreach ([-30, -28] as $hours) {
      [$task] = $this->createHeldCampaign($this->newsletter, $hours);
      $tasks[] = $task;
    }
    $calls = [];

    $result = $this->getResumeApi($calls, true)->resume(['newsletter_id' => $this->newsletter->getId()]);

    $this->assertSame(200, $result->status);
    $this->entityManager->clear();
    $repository = $this->diContainer->get(ScheduledTasksRepository::class);
    foreach ($tasks as $task) {
      $reloaded = $repository->findOneById((int)$task->getId());
      $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
      $this->assertNull($reloaded->getStatus());
      $this->assertTrue($reloaded->isOverdueSendConfirmed());
    }
  }

  /**
   * @return array{0: ScheduledTaskEntity, 1: SendingQueueEntity, 2: \MailPoet\Entities\SubscriberEntity}
   */
  private function createHeldCampaign(NewsletterEntity $newsletter, int $hoursFromNow = -30): array {
    $task = (new ScheduledTaskFactory())->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_PAUSED,
      Carbon::now()->addHours($hoursFromNow)->millisecond(0)
    );
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
    $queue = (new SendingQueueFactory())->create($task, $newsletter);
    $queue->setCountTotal(1);
    $subscriber = (new SubscriberFactory())->create();
    (new ScheduledTaskSubscriberFactory())->createUnprocessed($task, $subscriber);
    $this->entityManager->flush();
    $this->entityManager->refresh($newsletter);
    return [$task, $queue, $subscriber];
  }

  private function countTaskSubscribers(int $taskId): int {
    return $this->entityManager->getRepository(\MailPoet\Entities\ScheduledTaskSubscriberEntity::class)->count(['task' => $taskId]);
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
