<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class NewsletterTest extends \MailPoetTest {
  public $sendingQueue;
  public $segment2;
  public $segment1;
  /** @var Newsletter */
  public $newsletter;

  public function _before() {
    parent::_before();
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'My Standard Newsletter',
      'preheader' => 'Pre Header',
      'type' => Newsletter::TYPE_STANDARD,
    ]);

    $this->segment1 = Segment::createOrUpdate([
      'name' => 'Segment 1',
    ]);
    $association = NewsletterSegment::create();
    $association->newsletterId = $this->newsletter->id;
    $association->segmentId = $this->segment1->id;
    $association->save();

    $this->segment2 = Segment::createOrUpdate([
      'name' => 'Segment 2',
    ]);
    $association = NewsletterSegment::create();
    $association->newsletterId = $this->newsletter->id;
    $association->segmentId = $this->segment2->id;
    $association->save();

    $this->sendingQueue = SendingTask::create();
    $this->sendingQueue->newsletter_id = $this->newsletter->id;
    $this->sendingQueue->status = ScheduledTask::STATUS_SCHEDULED;
    $this->sendingQueue->save();
  }

  public function testItCanBeCreated() {
    expect($this->newsletter->id() > 0)->true();
    expect($this->newsletter->getErrors())->false();
  }

  public function testItHasASubject() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->subject)->equals($this->newsletter->subject);
  }

  public function testItHasAType() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->type)->equals($this->newsletter->type);
  }

  public function testItHasABody() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->body)->equals($this->newsletter->body);
  }

  public function testItHasPreheader() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->preheader)->equals($this->newsletter->preheader);
  }

  public function testItHasACreatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->updatedAt)
      ->equals($newsletter->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    assert($newsletter instanceof Newsletter);
    $createdAt = $newsletter->createdAt;

    sleep(1);

    $newsletter->subject = 'New Subject';
    $newsletter->save();

    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    expect($updatedNewsletter->createdAt)->equals($createdAt);
    $isTimeUpdated = (
      $updatedNewsletter->updatedAt > $updatedNewsletter->createdAt
    );
    expect($isTimeUpdated)->true();
  }

  public function testItCanBeQueued() {
    $queue = $this->newsletter->getQueue();
    expect($queue->id > 0)->true();
    expect($queue->newsletterId)->equals($this->newsletter->id);
  }

  public function testItCanHaveSegments() {
    $newsletterSegments = $this->newsletter->segments()->findArray();
    expect($newsletterSegments)->count(2);
    expect($newsletterSegments[0]['id'])->equals($this->segment1->id);
    expect($newsletterSegments[0]['name'])->equals('Segment 1');
    expect($newsletterSegments[1]['id'])->equals($this->segment2->id);
    expect($newsletterSegments[1]['name'])->equals('Segment 2');
  }

  public function testItCanHaveParentNewsletter() {
    $parentNewsletter = Newsletter::create();
    $parentNewsletter->type = Newsletter::TYPE_NOTIFICATION;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    $parent = $newsletter->parent()->findOne();
    expect($parent)->isInstanceOf('MailPoet\Models\Newsletter');
    expect($parent->id)->equals($parentNewsletter->id);
  }

  public function testItCanHaveDeletedSegments() {
    $this->segment2->delete();
    $this->newsletter->withSegments(true);
    $newsletterSegments = $this->newsletter->segments;
    expect($newsletterSegments)->count(2);
    expect($newsletterSegments[0]['id'])->equals($this->segment1->id);
    expect($newsletterSegments[0]['name'])->equals('Segment 1');
    expect($newsletterSegments[1]['id'])->equals($this->segment2->id);
    expect($newsletterSegments[1]['name'])->contains('Deleted');
  }

  public function testItCanCreateOrUpdate() {
    $isCreated = Newsletter::createOrUpdate(
      [
        'subject' => 'new newsletter',
        'type' => Newsletter::TYPE_STANDARD,
        'body' => 'body',
      ]);
    expect($isCreated->id() > 0)->true();
    expect($isCreated->getErrors())->false();

    $newsletter = Newsletter::where('subject', 'new newsletter')
      ->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $isUpdated = Newsletter::createOrUpdate(
      [
        'id' => $newsletter->id,
        'subject' => 'updated newsletter',
      ]);
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  public function testItCannotSetAnEmptyDeletedAt() {
    $this->newsletter->deletedAt = '';
    $newsletter = $this->newsletter->save();
    expect($newsletter->deletedAt)->equals('NULL');
  }

  public function testItCanHaveOptions() {
    $newsletterOptions = [
      'name' => 'event',
      'newsletter_type' => Newsletter::TYPE_WELCOME,
    ];
    $optionField = NewsletterOptionField::create();
    $optionField->hydrate($newsletterOptions);
    $optionField->save();
    $association = NewsletterOption::create();
    $association->newsletterId = $this->newsletter->id;
    $association->optionFieldId = (int)$optionField->id;
    $association->value = 'list';
    $association->save();
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($this->newsletter->id);
    expect($newsletter->event)->equals($association->value);
  }

  public function testItGetsArchiveNewslettersForSegments() {
    // clear the DB
    $this->_after();

    $types = [
      Newsletter::TYPE_STANDARD,
      Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $newsletters = [];
    $sendingQueues[] = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        [
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i],
        ]
      );
      $sendingQueues[$i] = SendingTask::create();
      $sendingQueues[$i]->newsletter_id = $newsletters[$i]->id;
      $sendingQueues[$i]->status = SendingQueue::STATUS_COMPLETED;
      $sendingQueues[$i]->save();
    }
    // set segment association for the last newsletter
    $newsletterSegment = NewsletterSegment::create();
    $newsletterSegment->newsletterId = end($newsletters[1])->id;
    $newsletterSegment->segmentId = 123;
    $newsletterSegment->save();

    expect(Newsletter::findMany())->count(2);

    // return archives in segment 123
    $results = Newsletter::getArchives([123]);
    expect($results)->count(1);
    expect($results[0]->id)->equals($newsletters[1]->id);
    expect($results[0]->type)->equals(Newsletter::TYPE_NOTIFICATION_HISTORY);
  }

  public function testItGetsAllArchiveNewsletters() {
    // clear the DB
    $this->_after();

    $types = [
      Newsletter::TYPE_STANDARD,
      Newsletter::TYPE_STANDARD, // should be returned
      Newsletter::TYPE_WELCOME,
      Newsletter::TYPE_AUTOMATIC,
      Newsletter::TYPE_NOTIFICATION,
      Newsletter::TYPE_NOTIFICATION_HISTORY, // should be returned
      Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $newsletters = [];
    $sendingQueues[] = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        [
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i],
        ]
      );
      $sendingQueues[$i] = SendingTask::create();
      $sendingQueues[$i]->newsletter_id = $newsletters[$i]->id;
      $sendingQueues[$i]->status = SendingQueue::STATUS_COMPLETED;
      $sendingQueues[$i]->save();
    }
    // set the sending queue status of the first newsletter to null
    $sendingQueues[0]->status = null;
    $sendingQueues[0]->save();

    // trash the last newsletter
    end($newsletters)->trash();

    expect(Newsletter::findMany())->count(7);

    // archives return only:
    // 1. STANDARD and NOTIFICATION HISTORY newsletters
    // 2. active newsletters (i.e., not trashed)
    // 3. with sending queue records that are COMPLETED
    $results = Newsletter::getArchives();
    expect($results)->count(2);
    expect($results[0]->id)->equals($newsletters[1]->id);
    expect($results[0]->type)->equals(Newsletter::TYPE_STANDARD);
    expect($results[1]->id)->equals($newsletters[5]->id);
    expect($results[1]->type)->equals(Newsletter::TYPE_NOTIFICATION_HISTORY);
  }

  public function testItGeneratesHashOnNewsletterSave() {
    expect(strlen($this->newsletter->hash))
      ->equals(Security::HASH_LENGTH);
  }

  public function testItRegeneratesHashOnNotificationHistoryCreation() {
    $notificationHistory = $this->newsletter->createNotificationHistory();
    expect($notificationHistory->hash)->notEquals($this->newsletter->hash);
    expect(strlen($notificationHistory->hash))
      ->equals(Security::HASH_LENGTH);
  }

  public function testItGetsQueueFromNewsletter() {
    expect($this->newsletter->queue()->findOne()->id)->equals($this->sendingQueue->id);
  }

  public function testItCanBeRestored() {
    $this->newsletter->status = Newsletter::STATUS_SENT;
    $this->newsletter->trash();
    $this->newsletter = $this->reloadNewsletter($this->newsletter);
    expect($this->newsletter->deletedAt)->notNull();
    $this->newsletter->restore();
    $this->newsletter = $this->reloadNewsletter($this->newsletter);
    expect($this->newsletter->deletedAt)->null();
    expect($this->newsletter->status)->equals(Newsletter::STATUS_SENT);
  }

  public function testItCanBulkRestoreNewsletters() {
    $statuses = [
      Newsletter::STATUS_DRAFT,
      Newsletter::STATUS_SENT,
      Newsletter::STATUS_SENDING,
    ];

    $newsletters = [];
    for ($i = 0; $i < count($statuses); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        [
          'subject' => 'Test',
          'preheader' => 'Some text',
          'type' => Newsletter::TYPE_STANDARD,
          'status' => $statuses[$i],
        ]
      );
    }

    Newsletter::filter('bulkTrash');
    expect(Newsletter::whereNull('deleted_at')->findArray())->isEmpty();

    Newsletter::filter('bulkRestore');
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->isEmpty();
  }

  public function testItDeletesSegmentAndQueueAssociationsWhenNewsletterIsDeleted() {
    $newsletter = $this->newsletter;

    // create multiple sending queues
    for ($i = 1; $i <= 5; $i++) {
      $sendingQueue = SendingTask::create();
      $sendingQueue->newsletterId = $newsletter->id;
      $sendingQueue->save();
    }

    // make sure relations exist
    expect(SendingQueue::where('newsletter_id', $newsletter->id)->findArray())->count(6);
    $newsletterSegments = NewsletterSegment::where('newsletter_id', $newsletter->id)->findArray();
    expect($newsletterSegments)->count(2);

    // delete newsletter and check that relations no longer exist
    $newsletter->delete();
    expect(SendingQueue::where('newsletter_id', $newsletter->id)->findArray())->isEmpty();
    $newsletterSegments = NewsletterSegment::where('newsletter_id', $newsletter->id)->findArray();
    expect($newsletterSegments)->isEmpty();
  }

  public function testItDeletesChildrenSegmentAndQueueAssociationsWhenParentNewsletterIsDeleted() {
    $parentNewsletter = $this->newsletter;
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parentNewsletter->id,
        ]
      );
      $sendingQueue = SendingTask::create();
      $sendingQueue->newsletterId = $newsletter->id;
      $sendingQueue->save();
      $newsletterSegment = NewsletterSegment::create();
      $newsletterSegment->newsletterId = $newsletter->id;
      $newsletterSegment->segmentId = 1;
      $newsletterSegment->save();
    }

    // make sure relations exist
    // 1 parent newsletter/queues, 2 parent segments and 5 children queues/newsletters/segments
    expect(Newsletter::findArray())->count(6);
    expect(SendingQueue::findArray())->count(6);
    expect(NewsletterSegment::findArray())->count(7);

    // delete parent newsletter and check that relations no longer exist
    $parentNewsletter->delete();
    expect(Newsletter::findArray())->count(0);
    expect(SendingQueue::findArray())->count(0);
    expect(NewsletterSegment::findArray())->count(0);
  }

  public function testItTrashesQueueAssociationsWhenNewsletterIsTrashed() {
    // create multiple sending queues
    $newsletter = $this->newsletter;
    for ($i = 1; $i <= 5; $i++) {
      $sendingQueue = SendingTask::create();
      $sendingQueue->newsletterId = $newsletter->id;
      $sendingQueue->save();
    }
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // trash newsletter and check that relations are trashed
    $newsletter->trash();
    // 5 queues + 1 created in _before() method
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItTrashesChildrenQueueAssociationsWhenParentNewsletterIsTrashed() {
    $parentNewsletter = $this->newsletter;
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parentNewsletter->id,
        ]
      );
      $sendingQueue = SendingTask::create();
      $sendingQueue->newsletterId = $newsletter->id;
      $sendingQueue->save();
    }
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // trash parent newsletter and check that relations are trashed
    $parentNewsletter->trash();
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItRestoresTrashedQueueAssociationsWhenNewsletterIsRestored() {
    // create multiple sending queues
    $sendingTasks = [];
    $newsletter = $this->newsletter;
    for ($i = 1; $i <= 5; $i++) {
      $sendingTask = SendingTask::create();
      $sendingTask->newsletterId = $newsletter->id;
      $sendingTask->deletedAt = date('Y-m-d H:i:s');
      $sendingTask->status = ScheduledTask::STATUS_SCHEDULED;
      $sendingTask->save();
      $sendingTasks[] = $sendingTask;
    }
    $inProgressTask = $sendingTasks[1];
    $inProgressTask->status = null;
    $inProgressTask->save();
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(5);
    // restore newsletter and check that relations are restored
    $newsletter->restore();
    // 5 queues + 1 created in _before() method
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
    // In progress task was switched to paused state
    expect(ScheduledTask::whereNull('deleted_at')->where('status', ScheduledTask::STATUS_PAUSED)->findArray())->count(1);
  }

  public function testItRestoresTrashedChildrenQueueAssociationsWhenParentNewsletterIsRestored() {
    // delete parent newsletter and sending queue
    $parentNewsletter = $this->newsletter;
    $parentNewsletter->deletedAt = date('Y-m-d H:i:s');
    $parentNewsletter->save();
    $parentSendingQueue = $this->sendingQueue;
    $parentSendingQueue->deletedAt = date('Y-m-d H:i:s');
    $parentSendingQueue->save();

    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parentNewsletter->id,
          'deleted_at' => date('Y-m-d H:i:s'),
        ]
      );
      $sendingQueue = SendingTask::create();
      $sendingQueue->newsletterId = $newsletter->id;
      $sendingQueue->deletedAt = date('Y-m-d H:i:s');
      $sendingQueue->save();
    }
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);

    // restore parent newsletter and check that relations are restored
    $parentNewsletter->restore();
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
  }

  public function testItDuplicatesNewsletter() {
    $originalNewsletter = $this->newsletter;
    $originalNewsletter->status = Newsletter::STATUS_SENT;
    $originalNewsletter->sentAt = $originalNewsletter->deletedAt = $originalNewsletter->createdAt = $originalNewsletter->updatedAt = date( '2000-m-d H:i:s');
    $originalNewsletter->save();
    $data = ['subject' => 'duplicate newsletter'];
    $duplicateNewsletter = $this->newsletter->duplicate($data);
    $duplicateNewsletter = Newsletter::findOne($duplicateNewsletter->id);
    assert($duplicateNewsletter instanceof Newsletter);
    // hash is different
    expect($duplicateNewsletter->hash)->notEquals($this->newsletter->hash);
    expect(strlen($duplicateNewsletter->hash))->equals(Security::HASH_LENGTH);
    // status is set to draft
    expect($duplicateNewsletter->status)->equals(Newsletter::STATUS_DRAFT);
    // sent at/delete at dates are null
    expect($duplicateNewsletter->sentAt)->null();
    expect($duplicateNewsletter->deletedAt)->null();
    // created at/updated at dates are different
    expect($duplicateNewsletter->createdAt)->notEquals($originalNewsletter->createdAt);
    expect($duplicateNewsletter->updatedAt)->notEquals($originalNewsletter->updatedAt);
    // body and subject are the same
    expect($duplicateNewsletter->body)->equals($originalNewsletter->body);
    expect($duplicateNewsletter->subject)->equals($data['subject']);
  }

  public function testItGetsAndDecodesNewsletterOptionMetaField() {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'Test Option Meta Field',
        'preheader' => 'Pre Header',
        'type' => Newsletter::TYPE_AUTOMATIC,
      ]
    );
    $newsletterOptionField = NewsletterOptionField::create();
    $newsletterOptionField->hydrate(
      [
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
        'name' => 'meta',
      ]
    );
    $newsletterOptionField->save();
    $newsletterOption = NewsletterOption::create();
    $meta = ['some' => 'value'];
    $newsletterOption->hydrate(
      [
        'newsletter_id' => $newsletter->id,
        'option_field_id' => $newsletterOptionField->id,
        'value' => json_encode($meta),
      ]
    );
    $newsletterOption->save();

    // by default meta option does not exist on newsletter object
    expect($newsletter->getMeta())->isEmpty();

    // if meta option exists, it should be returned as an array
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    expect($newsletter->getMeta())->equals($meta);
  }

  public function testPausesTaskWhenNewsletterWithActivationIsDisabled() {
    $newslettersWithActivation = [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_WELCOME, Newsletter::TYPE_AUTOMATIC];
    foreach ($newslettersWithActivation as $type) {
      $newsletter = Newsletter::createOrUpdate([
        'type' => $type,
      ]);
      $task = ScheduledTask::createOrUpdate(['status' => ScheduledTask::STATUS_SCHEDULED]);
      SendingQueue::createOrUpdate([
        'newsletter_id' => $newsletter->id(),
        'task_id' => $task->id(),
      ]);
      $newsletter->setStatus(Newsletter::STATUS_DRAFT);
      $taskFound = ScheduledTask::findOne($task->id());
      expect($taskFound->status)->equals(ScheduledTask::STATUS_PAUSED);
    }
  }

  public function testUnpausesTaskWhenNewsletterWithActivationIsEnabled() {
    $newslettersWithActivation = [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_WELCOME, Newsletter::TYPE_AUTOMATIC];
    foreach ($newslettersWithActivation as $type) {
      $newsletter = Newsletter::createOrUpdate([
        'type' => $type,
        'body' => '["x", "y"]',
      ]);
      $task = ScheduledTask::createOrUpdate([
        'status' => ScheduledTask::STATUS_PAUSED,
        'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
      ]);
      SendingQueue::createOrUpdate([
        'newsletter_id' => $newsletter->id(),
        'task_id' => $task->id(),
      ]);
      $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
      $taskFound = ScheduledTask::findOne($task->id());
      expect($taskFound->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    }
  }

  public function testBlocksActivationOfEmptyNewsletter() {
    $newsletter = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_NOTIFICATION,
      'body' => '[]',
      'status' => Newsletter::STATUS_DRAFT,
    ]);
    $newsletter = $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
    expect($newsletter->status)->equals(Newsletter::STATUS_DRAFT);
    expect($newsletter->getErrors())->notEmpty();
  }

  private function reloadNewsletter(Newsletter $newsletter): Newsletter {
    $newsletter = Newsletter::findOne($newsletter->id);
    assert($newsletter instanceof Newsletter);
    return $newsletter;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsUnsubscribes::$_table);
  }
}
