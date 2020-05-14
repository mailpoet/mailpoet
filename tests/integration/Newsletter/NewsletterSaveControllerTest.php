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

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->saveController = $this->diContainer->get(NewsletterSaveController::class);
  }

  public function testItCanSaveANewsletter() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $newsletterData = [
      'id' => $newsletter->getId(),
      'subject' => 'Updated subject',
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect($newsletter->getSubject())->equals('Updated subject');
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
    assert($updatedQueue instanceof SendingQueueEntity); // PHPStan
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
    assert($updatedQueue instanceof SendingQueueEntity); // PHPStan
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

    expect($scheduleOption->getValue())->equals('0 14 * * 1');

    // schedule should be recalculated when options change
    $newsletterData['options']['intervalType'] = PostNotificationScheduler::INTERVAL_IMMEDIATELY;
    $savedNewsletter = $this->saveController->save($newsletterData);

    $scheduleOption = $savedNewsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();

    expect($scheduleOption->getValue())->equals('* * * * *');
  }

  public function testItCanReschedulePreviouslyScheduledSendingQueueJobs() {
    $this->createPostNotificationOptions();

    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION);

    $currentTime = Carbon::now();
    $queue1 = $this->createQueueWithTask($newsletter);
    $task1 = $queue1->getTask();
    assert($task1 instanceof ScheduledTaskEntity); // PHPStan
    $task1->setScheduledAt($currentTime);

    $queue2 = $this->createQueueWithTask($newsletter);
    $task2 = $queue2->getTask();
    assert($task2 instanceof ScheduledTaskEntity); // PHPStan
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
    expect($task1->getScheduledAt())->equals(Scheduler::getNextRunDate($scheduleOption->getValue()));
    expect($task2->getScheduledAt())->null();
  }

  public function testItCanModifySegmentsOfExistingNewsletter() {
    $segment = new SegmentEntity();
    $segment->setType(SegmentEntity::TYPE_DEFAULT);
    $segment->setName('Segment 1');
    $segment->setDescription('Segment 1 description');
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
    expect($newsletter->getNewsletterSegments()->first()->getSegment()->getName())->equals('Segment 1');
  }

  public function testItDeletesSendingQueueAndSetsNewsletterStatusToDraftWhenItIsUnscheduled() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SCHEDULED);

    $queue = $this->createQueueWithTask($newsletter);
    $queue->setNewsletterRenderedBody([
      'html' => 'html',
      'text' => 'text',
    ]);
    $task = $queue->getTask();
    assert($task instanceof ScheduledTaskEntity); // PHPStan
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

  public function _after() {
    $this->cleanup();
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
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

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterSegmentEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
