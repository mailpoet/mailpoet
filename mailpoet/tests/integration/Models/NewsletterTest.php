<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\NewsletterOption as NewsletterOptionFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class NewsletterTest extends \MailPoetTest {
  public $sendingQueue;
  public $segment2;
  public $segment1;
  /** @var Newsletter */
  public $newsletter;

  /** @var NewsletterOptionFactory */
  private $newsletterOptionFactory;

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

    $this->newsletterOptionFactory = new NewsletterOptionFactory();
  }

  public function testItCanBeCreated() {
    expect($this->newsletter->id() > 0)->true();
    expect($this->newsletter->getErrors())->false();
  }

  public function testItHasASubject() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->subject)->equals($this->newsletter->subject);
  }

  public function testItHasAType() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->type)->equals($this->newsletter->type);
  }

  public function testItHasABody() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->body)->equals($this->newsletter->body);
  }

  public function testItHasPreheader() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->preheader)->equals($this->newsletter->preheader);
  }

  public function testItHasACreatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->updatedAt)->equals($newsletter->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    $createdAt = $newsletter->createdAt;

    sleep(1);

    $newsletter->subject = 'New Subject';
    $newsletter->save();

    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $updatedNewsletter);
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
    expect($newsletterSegments[1]['name'])->stringContainsString('Deleted');
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
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->subject)->equals('new newsletter');

    $isUpdated = Newsletter::createOrUpdate(
      [
        'id' => $newsletter->id,
        'subject' => 'updated newsletter',
      ]);
    $newsletter = Newsletter::findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  public function testItCannotSetAnEmptyDeletedAt() {
    $this->newsletter->deletedAt = '';
    $newsletter = $this->newsletter->save();
    expect($newsletter->deletedAt)->equals('NULL');
  }

  public function testItCanHaveOptions() {
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterOption = $this->newsletterOptionFactory->create($newsletterEntity, 'event', 'list');

    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_STANDARD)
      ->findOne($this->newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    expect($newsletter->event)->equals($newsletterOption->getValue());
  }

  public function testItGeneratesHashOnNewsletterSave() {
    expect(strlen($this->newsletter->hash))
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
    $this->assertInstanceOf(Newsletter::class, $duplicateNewsletter);
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

    $meta = ['some' => 'value'];
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->assertIsString(json_encode($meta));
    $this->newsletterOptionFactory->create($newsletterEntity, 'meta', json_encode($meta));

    // by default meta option does not exist on newsletter object
    expect($newsletter->getMeta())->isEmpty();

    // if meta option exists, it should be returned as an array
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    $this->assertInstanceOf(Newsletter::class, $newsletter);
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
      $this->assertInstanceOf(ScheduledTask::class, $taskFound);
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
      $this->assertInstanceOf(ScheduledTask::class, $taskFound);
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
    $this->assertInstanceOf(Newsletter::class, $newsletter);
    return $newsletter;
  }
}
