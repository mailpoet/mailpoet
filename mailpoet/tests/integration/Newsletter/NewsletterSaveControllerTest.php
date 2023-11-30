<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\NewsletterOptionField;
use MailPoet\WP\Functions as WPFunctions;
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
    verify($newsletter->getType())->equals('Updated type');
    verify($newsletter->getSubject())->equals('Updated subject');
    verify($newsletter->getPreheader())->equals('Updated preheader');
    verify($newsletter->getBody())->equals(['value' => 'Updated body']);
    verify($newsletter->getSenderName())->equals('Updated sender name');
    verify($newsletter->getSenderAddress())->equals('Updated sender address');
    verify($newsletter->getReplyToName())->equals('Updated reply-to name');
    verify($newsletter->getReplyToAddress())->equals('Updated reply-to address');
    verify($newsletter->getGaCampaign())->equals('Updated GA campaign');
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
    verify($updatedQueue->getNewsletterRenderedSubject())->null();
    verify($updatedQueue->getNewsletterRenderedBody())->null();
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
    verify($updatedQueue->getNewsletterRenderedSubject())->same('My Updated Newsletter');
    verify($updatedQueue->getNewsletterRenderedBody())->arrayHasKey('html');
    verify($updatedQueue->getNewsletterRenderedBody())->arrayHasKey('text');
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
    $scheduleOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption = null) {
      if ($newsletterOption === null) return false; // PHPStan
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();

    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan
    verify($scheduleOption->getValue())->equals('0 14 * * 1');

    // schedule should be recalculated when options change
    $newsletterData['options']['intervalType'] = PostNotificationScheduler::INTERVAL_IMMEDIATELY;
    $savedNewsletter = $this->saveController->save($newsletterData);

    $scheduleOption = $savedNewsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption = null) {
      if ($newsletterOption === null) return false; // PHPStan
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();

    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan
    verify($scheduleOption->getValue())->equals('* * * * *');
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
    $scheduleOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption = null) {
      if ($newsletterOption === null) return false; // PHPStan
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'schedule';
    })->first();
    verify($task1->getScheduledAt())->notEquals($currentTime);
    $this->assertInstanceOf(NewsletterOptionEntity::class, $scheduleOption); // PHPStan

    verify($task1->getScheduledAt())->equals($this->scheduler->getNextRunDate($scheduleOption->getValue()));
    verify($task2->getScheduledAt())->null();
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
    verify(count($newsletter->getNewsletterSegments()))->equals(1);
    $newsletterSegment = $newsletter->getNewsletterSegments()->first();
    $this->assertInstanceOf(NewsletterSegmentEntity::class, $newsletterSegment); // PHPStan
    $segment = $newsletterSegment->getSegment();
    $this->assertInstanceOf(SegmentEntity::class, $segment); // PHPStan
    verify($segment->getName())->equals('Segment 1');
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
    verify(count($newsletter->getNewsletterSegments()))->equals(0);
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
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($newsletter->getLatestQueue())->null();
    verify($this->diContainer->get(SendingQueuesRepository::class)->findOneById($queueId))->null();
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
    verify($settings->get('sender.name'))->same('Test sender');
    verify($settings->get('sender.address'))->same('test@example.com');
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
    verify($settings->get('sender'))->null();
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
    verify($settings->get('sender.name'))->same('Test sender');
    verify($settings->get('sender.address'))->same('test@example.com');
  }

  public function testItDuplicatesNewsletter() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $duplicate = $this->saveController->duplicate($newsletter);
    verify($duplicate->getSubject())->equals('Copy of ' . $newsletter->getSubject());
    verify($duplicate->getHash())->isString();
    verify($duplicate->getHash())->notEmpty();
    verify($duplicate->getHash())->notEquals($newsletter->getHash());
    verify($duplicate->getBody())->equals($newsletter->getBody());
    verify($duplicate->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
  }

  public function testItDuplicatesNewsletterWithAssociatedPost() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $wp = $this->diContainer->get(WPFunctions::class);
    $postId = $wp->wpInsertPost(['post_content' => 'newsletter content']);
    $newsletter->setWpPostId($postId);
    $this->entityManager->flush();
    $duplicate = $this->saveController->duplicate($newsletter);
    verify($duplicate->getWpPostId())->notEquals($postId);
    $post = $wp->getPost($duplicate->getWpPostId());
    verify($post->post_content)->equals('newsletter content'); // @phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItCreatesNewNewsletter() {
    $data = [
      'subject' => 'My First Newsletter',
      'type' => NewsletterEntity::TYPE_STANDARD,
    ];

    $newsletter = $this->saveController->save($data);
    verify($newsletter->getSubject())->equals($data['subject']);
    verify($newsletter->getType())->equals($data['type']);
    verify($newsletter->getHash())->notNull();
    verify($newsletter->getId())->notNull();
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
    verify($newsletter->getSenderName())->same('Sender');
    verify($newsletter->getSenderAddress())->same('sender@test.com');
    verify($newsletter->getReplyToName())->same('Reply');
    verify($newsletter->getReplyToAddress())->same('reply@test.com');
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
    $task->setType(SendingQueue::TASK_TYPE);
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
      (new NewsletterOptionField())->findOrCreate($optionName, NewsletterEntity::TYPE_NOTIFICATION);
    }
    $this->entityManager->flush();
  }
}
