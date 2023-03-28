<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;

class NewsletterSaveControllerTest extends \MailPoetTest {
  /** @var NewsletterSaveController */
  private $saveController;

  /** @var Scheduler */
  private $scheduler;

  public function _before() {
    parent::_before();
    $this->saveController = $this->diContainer->get(NewsletterSaveController::class);
    $this->scheduler = $this->diContainer->get(Scheduler::class);
  }

  public function testItCanSaveANewsletter() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $newsletterData = [
      'id' => $newsletter->getId(),
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

    $newsletter = $this->saveController->save($newsletterData);
    expect($newsletter->getType())->equals('Updated type');
    expect($newsletter->getSubject())->equals('Updated subject');
    expect($newsletter->getPreheader())->equals('Updated preheader');
    expect($newsletter->getBody())->equals(['value' => 'Updated body']);
    expect($newsletter->getSenderName())->equals('Updated sender name');
    expect($newsletter->getSenderAddress())->equals('Updated sender address');
    expect($newsletter->getReplyToName())->equals('Updated reply-to name');
    expect($newsletter->getReplyToAddress())->equals('Updated reply-to address');
    expect($newsletter->getGaCampaign())->equals('Updated GA campaign');
  }

  public function testItDoesNotRerenderPostNotificationsUponUpdate() {
    $this->createPostNotificationOptions();
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION);
    $this->createQueueWithTask($newsletter);

    $newsletterData = [
      'id' => $newsletter->getId(),
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $newsletter = $this->saveController->save($newsletterData);
    $updatedQueue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $updatedQueue); // PHPStan
    expect($updatedQueue->getNewsletterRenderedSubject())->null();
    expect($updatedQueue->getNewsletterRenderedBody())->null();
  }

  public function testItCanRerenderQueueUponSave() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $this->createQueueWithTask($newsletter);

    $newsletterData = [
      'id' => $newsletter->getId(),
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $this->saveController->save($newsletterData);
    $updatedQueue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $updatedQueue); // PHPStan
    expect($updatedQueue->getNewsletterRenderedSubject())->same('My Updated Newsletter');
    expect($updatedQueue->getNewsletterRenderedBody())->hasKey('html');
    expect($updatedQueue->getNewsletterRenderedBody())->hasKey('text');
  }

  public function testItCanUpdatePostNotificationScheduleUponSave() {
    $this->createPostNotificationOptions();
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $newsletterData = [
      'id' => $newsletter->getId(),
      'type' => NewsletterEntity::TYPE_NOTIFICATION,
      'subject' => 'Newsletter',
      'options' => [
        'intervalType' => PostNotificationScheduler::INTERVAL_WEEKLY,
        'timeOfDay' => '50400',
        'weekDay' => '1',
        'monthDay' => '0',
        'nthWeekDay' => '1',
        'schedule' => '0 14 * * 1',
      ],
    ];
    $newsletter = $this->saveController->save($newsletterData);
    $scheduleOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();

    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan
    expect($scheduleOption->getValue())->equals('0 14 * * 1');

    // schedule should be recalculated when options change
    $newsletterData['options']['intervalType'] = PostNotificationScheduler::INTERVAL_IMMEDIATELY;
    $savedNewsletter = $this->saveController->save($newsletterData);

    $scheduleOption = $savedNewsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();

    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan
    expect($scheduleOption->getValue())->equals('* * * * *');
  }

  public function testItCanReschedulePreviouslyScheduledSendingQueueJobs() {
    $this->createPostNotificationOptions();

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION);

    $currentTime = Carbon::now();
    $queue1 = $this->createQueueWithTask($newsletter);
    $task1 = $queue1->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task1); // PHPStan
    $task1->setScheduledAt($currentTime);

    $queue2 = $this->createQueueWithTask($newsletter);
    $task2 = $queue2->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task2); // PHPStan
    $task2->setStatus(null);

    $this->entityManager->flush();

    $newsletterData = [
      'id' => $newsletter->getId(),
      'type' => NewsletterEntity::TYPE_NOTIFICATION,
      'subject' => 'Newsletter',
      'options' => [
        // weekly on Monday @ 7am
        'intervalType' => PostNotificationScheduler::INTERVAL_WEEKLY,
        'timeOfDay' => '25200',
        'weekDay' => '1',
        'monthDay' => '0',
        'nthWeekDay' => '1',
        'schedule' => '0 7 * * 1',
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    $scheduleOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();
    expect($task1->getScheduledAt())->notEquals($currentTime);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan

    expect($task1->getScheduledAt())->equals($this->scheduler->getNextRunDate($scheduleOption->getValue()));
    expect($task2->getScheduledAt())->null();
  }

  public function testItCanModifySegmentsOfExistingNewsletter() {
    $segment = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment 1 description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    $fakeSegmentId = 1;

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $newsletterData = [
      'id' => $newsletter->getId(),
      'subject' => 'My Updated Newsletter',
      'segments' => [
        ['id' => $segment->getId()],
        $fakeSegmentId,
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect(count($newsletter->getNewsletterSegments()))->equals(1);
    $newsletterSegment = $newsletter->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $newsletterSegment); // PHPStan
    $segment = $newsletterSegment->getSegment();
    $this->assertInstanceOf(SegmentEntity::class, $segment); // PHPStan
    expect($segment->getName())->equals('Segment 1');
  }

  public function testItDoesNotSaveSegmentsForAutomationEmails() {
    $segment = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment 1 description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    $fakeSegmentId = 1;

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_AUTOMATION);
    $newsletterData = [
      'id' => $newsletter->getId(),
      'segments' => [
        ['id' => $segment->getId()],
        $fakeSegmentId,
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect(count($newsletter->getNewsletterSegments()))->equals(0);
  }

  public function testItDeletesSendingQueueAndSetsNewsletterStatusToDraftWhenItIsUnscheduled() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SCHEDULED);

    $queue = $this->createQueueWithTask($newsletter);
    $queue->setNewsletterRenderedBody([
      'html' => 'html',
      'text' => 'text',
    ]);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task); // PHPStan
    $task->setScheduledAt(Carbon::now());
    $this->entityManager->flush();
    $queueId = $queue->getId();

    $newsletterData = [
      'id' => $newsletter->getId(),
      'options' => [
        'isScheduled' => false,
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    expect($newsletter->getLatestQueue())->null();
    expect($this->diContainer->get(SendingQueuesRepository::class)->findOneById($queueId))->null();
  }

  public function testItSavesDefaultSenderIfNeeded() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('sender', null);

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $data = [
      'id' => $newsletter->getId(),
      'subject' => 'My New Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'sender_name' => 'Test sender',
      'sender_address' => 'test@example.com',
    ];

    $this->saveController->save($data);
    expect($settings->get('sender.name'))->same('Test sender');
    expect($settings->get('sender.address'))->same('test@example.com');
  }

  public function testItDoesntSaveDefaultSenderWhenEmptyValues() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('sender', null);

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $data = [
      'id' => $newsletter->getId(),
      'subject' => 'My New Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'sender_name' => '',
      'sender_address' => null,
    ];

    $this->saveController->save($data);
    expect($settings->get('sender'))->null();
  }

  public function testItDoesntOverrideDefaultSender() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('sender', [
      'name' => 'Test sender',
      'address' => 'test@example.com',
    ]);

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $data = [
      'id' => $newsletter->getId(),
      'subject' => 'My New Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
      'sender_name' => 'Another test sender',
      'sender_address' => 'another.test@example.com',
    ];

    $this->saveController->save($data);
    expect($settings->get('sender.name'))->same('Test sender');
    expect($settings->get('sender.address'))->same('test@example.com');
  }

  public function testItDuplicatesNewsletter() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $duplicate = $this->saveController->duplicate($newsletter);
    expect($duplicate->getSubject())->equals('Copy of ' . $newsletter->getSubject());
    expect($duplicate->getHash())->string();
    expect($duplicate->getHash())->notEmpty();
    expect($duplicate->getHash())->notEquals($newsletter->getHash());
    expect($duplicate->getBody())->equals($newsletter->getBody());
    expect($duplicate->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
  }

  public function testItCreatesNewNewsletter() {
    $data = [
      'subject' => 'My First Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];

    $newsletter = $this->saveController->save($data);
    expect($newsletter->getSubject())->equals($data['subject']);
    expect($newsletter->getType())->equals($data['type']);
    expect($newsletter->getHash())->notNull();
    expect($newsletter->getId())->notNull();
  }

  public function testItCreatesNewsletterWithDefaultSender() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('sender', [
      'name' => 'Sender',
      'address' => 'sender@test.com',
    ]);
    $settings->set('reply_to', [
      'name' => 'Reply',
      'address' => 'reply@test.com',
    ]);

    $data = [
      'subject' => 'My First Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];
    $newsletter = $this->saveController->save($data);
    expect($newsletter->getSenderName())->same('Sender');
    expect($newsletter->getSenderAddress())->same('sender@test.com');
    expect($newsletter->getReplyToName())->same('Reply');
    expect($newsletter->getReplyToAddress())->same('reply@test.com');
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $body = (array)\json_decode(Fixtures::get('newsletter_body_template'), true);
    $newsletter->setBody($body);
    $newsletter->setStatus($status);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createQueueWithTask(NewsletterEntity $newsletter): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);

    $newsletter->getQueues()->add($queue);
    $this->entityManager->flush();
    return $queue;
  }

  private function createPostNotificationOptions() {
    $newsletterOptions = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];

    foreach ($newsletterOptions as $optionName) {
      $newsletterOptionField = new NewsletterOptionFieldEntity();
      $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_NOTIFICATION);
      $newsletterOptionField->setName($optionName);
      $this->entityManager->persist($newsletterOptionField);
    }
    $this->entityManager->flush();
  }
}
