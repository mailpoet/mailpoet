<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;

class NewsletterSaveControllerTest extends \MailPoetTest {
  /** @var Newsletter */
  private $newsletter;

  /** @var Newsletter */
  private $postNotification;

  /** @var NewsletterSaveController */
  private $saveController;

  public function _before() {
    parent::_before();
    $this->saveController = $this->diContainer->get(NewsletterSaveController::class);
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'My Standard Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);

    $this->postNotification = Newsletter::createOrUpdate([
      'subject' => 'My Post Notification',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_NOTIFICATION,
    ]);

    NewsletterOptionField::createOrUpdate([
      'name' => 'isScheduled',
      'newsletter_type' => 'standard',
    ]);
    NewsletterOptionField::createOrUpdate([
      'name' => 'scheduledAt',
      'newsletter_type' => 'standard',
    ]);
  }

  public function testItCanSaveANewsletter() {
    $newsletterData = [
      'id' => $this->newsletter->id,
      'subject' => 'Updated subject',
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect($newsletter->getSubject())->equals('Updated subject');
  }

  public function testItDoesNotRerenderPostNotificationsUponUpdate() {
    // create newsletter options
    $newsletterOptions = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];
    foreach ($newsletterOptions as $option) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
      $newsletterOptionField->save();
    }

    $sendingQueue = SendingTask::create();
    $sendingQueue->newsletterId = $this->postNotification->id;
    $sendingQueue->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue->newsletterRenderedBody = null;
    $sendingQueue->newsletterRenderedSubject = null;
    $sendingQueue->save();
    expect($sendingQueue->getErrors())->false();

    $newsletterData = [
      'id' => $this->postNotification->id,
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $newsletter = $this->saveController->save($newsletterData);
    $updatedQueue = SendingQueue::where('newsletter_id', $this->postNotification->id)
      ->findOne()
      ->asArray();

    expect($updatedQueue['newsletter_rendered_body'])->null();
    expect($updatedQueue['newsletter_rendered_subject'])->null();
  }

  public function testItCanRerenderQueueUponSave() {
    $sendingQueue = SendingTask::create();
    $sendingQueue->newsletterId = $this->newsletter->id;
    $sendingQueue->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue->newsletterRenderedBody = null;
    $sendingQueue->newsletterRenderedSubject = null;
    $sendingQueue->save();
    expect($sendingQueue->getErrors())->false();

    $newsletterData = [
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ];

    $newsletter = $this->saveController->save($newsletterData);
    $updatedQueue = SendingQueue::where('newsletter_id', $this->newsletter->id)
      ->findOne()
      ->asArray();

    expect($updatedQueue['newsletter_rendered_body'])->hasKey('html');
    expect($updatedQueue['newsletter_rendered_body'])->hasKey('text');
    expect($updatedQueue['newsletter_rendered_subject'])->equals('My Updated Newsletter');
  }

  public function testItCanUpdatePostNotificationScheduleUponSave() {
    $newsletterOptions = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];
    foreach ($newsletterOptions as $option) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
      $newsletterOptionField->save();
    }

    $newsletterData = [
      'id' => $this->newsletter->id,
      'type' => Newsletter::TYPE_NOTIFICATION,
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
      return $newsletterOption->getOptionField()->getName() === 'schedule';
    })->first();

    expect($scheduleOption->getValue())->equals('0 14 * * 1');

    // schedule should be recalculated when options change
    $newsletterData['options']['intervalType'] = PostNotificationScheduler::INTERVAL_IMMEDIATELY;
    $savedNewsletter = $this->saveController->save($newsletterData);

    $scheduleOption = $savedNewsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      return $newsletterOption->getOptionField()->getName() === 'schedule';
    })->first();

    expect($scheduleOption->getValue())->equals('* * * * *');
  }

  public function testItCanReschedulePreviouslyScheduledSendingQueueJobs() {
    // create newsletter options
    $newsletterOptions = [
      'intervalType',
      'timeOfDay',
      'weekDay',
      'monthDay',
      'nthWeekDay',
      'schedule',
    ];
    foreach ($newsletterOptions as $option) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = Newsletter::TYPE_NOTIFICATION;
      $newsletterOptionField->save();
    }

    // create sending queues
    $currentTime = Carbon::now();
    $sendingQueue1 = SendingTask::create();
    $sendingQueue1->newsletterId = 1;
    $sendingQueue1->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue1->scheduledAt = $currentTime;
    $sendingQueue1->save();

    $sendingQueue2 = SendingTask::create();
    $sendingQueue2->newsletterId = 1;
    $sendingQueue2->save();

    // save newsletter via router
    $newsletterData = [
      'id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION,
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
    /** @var SendingQueue $sendingQueue1 */
    $sendingQueue1 = SendingQueue::findOne($sendingQueue1->id);
    $sendingQueue1 = SendingTask::createFromQueue($sendingQueue1);
    /** @var SendingQueue $sendingQueue2 */
    $sendingQueue2 = SendingQueue::findOne($sendingQueue2->id);
    $sendingQueue2 = SendingTask::createFromQueue($sendingQueue2);
    expect($sendingQueue1->scheduledAt)->notEquals($currentTime);

    $scheduleOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      return $newsletterOption->getOptionField()->getName() === 'schedule';
    })->first();

    expect($sendingQueue1->scheduledAt)->equals(
      Scheduler::getNextRunDate($scheduleOption->getValue())
    );
    expect($sendingQueue2->scheduledAt)->null();
  }

  public function testItCanModifySegmentsOfExistingNewsletter() {
    $segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $fakeSegmentId = 1;

    $newsletterData = [
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter',
      'segments' => [
        $segment1->asArray(),
        $fakeSegmentId,
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    expect(count($newsletter->getNewsletterSegments()))->equals(1);
    expect($newsletter->getNewsletterSegments()->first()->getSegment()->getName())->equals('Segment 1');
  }

  public function testItDeletesSendingQueueAndSetsNewsletterStatusToDraftWhenItIsUnscheduled() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $sendingQueue = SendingTask::create();
    $sendingQueue->newsletterId = $newsletter->id;
    $sendingQueue->newsletterRenderedBody = [
      'html' => 'html',
      'text' => 'text',
    ];
    $sendingQueue->status = SendingQueue::STATUS_SCHEDULED;
    $sendingQueue->scheduledAt = Carbon::now()->format('Y-m-d H:i');
    $sendingQueue->save();
    expect($sendingQueue->getErrors())->false();

    $newsletterData = [
      'id' => $newsletter->id,
      'options' => [
        'isScheduled' => false,
      ],
    ];

    $newsletter = $this->saveController->save($newsletterData);
    $sendingQueue = SendingQueue::findOne($sendingQueue->id);
    expect($newsletter->getStatus())->equals(Newsletter::STATUS_DRAFT);
    expect($sendingQueue)->false();
  }

  public function testItSavesDefaultSenderIfNeeded() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('sender', null);

    $data = [
      'id' => $this->newsletter->id,
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD,
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

    $data = [
      'id' => $this->newsletter->id,
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD,
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

    $data = [
      'id' => $this->newsletter->id,
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD,
      'sender_name' => 'Another test sender',
      'sender_address' => 'another.test@example.com',
    ];

    $this->saveController->save($data);
    expect($settings->get('sender.name'))->same('Test sender');
    expect($settings->get('sender.address'))->same('test@example.com');
  }
}
