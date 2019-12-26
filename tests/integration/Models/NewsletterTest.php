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
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class NewsletterTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'My Standard Newsletter',
      'preheader' => 'Pre Header',
      'type' => Newsletter::TYPE_STANDARD,
    ]);

    $this->segment_1 = Segment::createOrUpdate([
      'name' => 'Segment 1',
    ]);
    $association = NewsletterSegment::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->segment_id = $this->segment_1->id;
    $association->save();

    $this->segment_2 = Segment::createOrUpdate([
      'name' => 'Segment 2',
    ]);
    $association = NewsletterSegment::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->segment_id = $this->segment_2->id;
    $association->save();

    $this->sending_queue = SendingTask::create();
    $this->sending_queue->newsletter_id = $this->newsletter->id;
    $this->sending_queue->save();
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
    expect($newsletter->created_at)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->updated_at)
      ->equals($newsletter->created_at);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    $created_at = $newsletter->created_at;

    sleep(1);

    $newsletter->subject = 'New Subject';
    $newsletter->save();

    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->created_at)->equals($created_at);
    $is_time_updated = (
      $updated_newsletter->updated_at > $updated_newsletter->created_at
    );
    expect($is_time_updated)->true();
  }

  public function testItCanBeQueued() {
    $queue = $this->newsletter->getQueue();
    expect($queue->id > 0)->true();
    expect($queue->newsletter_id)->equals($this->newsletter->id);
  }

  public function testItCanHaveSegments() {
    $newsletter_segments = $this->newsletter->segments()->findArray();
    expect($newsletter_segments)->count(2);
    expect($newsletter_segments[0]['id'])->equals($this->segment_1->id);
    expect($newsletter_segments[0]['name'])->equals('Segment 1');
    expect($newsletter_segments[1]['id'])->equals($this->segment_2->id);
    expect($newsletter_segments[1]['name'])->equals('Segment 2');
  }

  public function testItCanHaveParentNewsletter() {
    $parent_newsletter = Newsletter::create();
    $parent_newsletter->type = Newsletter::TYPE_NOTIFICATION;
    $parent_newsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $parent_newsletter->id;
    $newsletter->save();
    $parent = $newsletter->parent()->findOne();
    expect($parent)->isInstanceOf('MailPoet\Models\Newsletter');
    expect($parent->id)->equals($parent_newsletter->id);
  }

  public function testItCanHaveDeletedSegments() {
    $this->segment_2->delete();
    $this->newsletter->withSegments(true);
    $newsletter_segments = $this->newsletter->segments;
    expect($newsletter_segments)->count(2);
    expect($newsletter_segments[0]['id'])->equals($this->segment_1->id);
    expect($newsletter_segments[0]['name'])->equals('Segment 1');
    expect($newsletter_segments[1]['id'])->equals($this->segment_2->id);
    expect($newsletter_segments[1]['name'])->contains('Deleted');
  }

  public function testItCanHaveStatistics() {
    $newsletter = $this->newsletter;
    $sending_queue = $this->sending_queue;

    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);

    $opens = StatisticsOpens::create();
    $opens->subscriber_id = $subscriber->id;
    $opens->newsletter_id = $this->newsletter->id;
    $opens->queue_id = $sending_queue->id;
    $opens->save();

    $clicks = StatisticsClicks::create();
    $clicks->subscriber_id = $subscriber->id;
    $clicks->newsletter_id = $this->newsletter->id;
    $clicks->queue_id = $sending_queue->id;
    $clicks->link_id = 0;
    $clicks->count = 0;
    $clicks->save();

    $unsubscribes = StatisticsUnsubscribes::create();
    $unsubscribes->subscriber_id = $subscriber->id;
    $unsubscribes->newsletter_id = $this->newsletter->id;
    $unsubscribes->queue_id = $sending_queue->id;
    $unsubscribes->save();

    $newsletter->queue = $newsletter->getQueue()->asArray();
    $statistics = $newsletter->getStatistics($this->makeEmpty(WCHelper::class));
    expect($statistics['opened'])->equals(1);
    expect($statistics['clicked'])->equals(1);
    expect($statistics['unsubscribed'])->equals(1);
  }

  public function testItCanCreateOrUpdate() {
    $is_created = Newsletter::createOrUpdate(
      [
        'subject' => 'new newsletter',
        'type' => Newsletter::TYPE_STANDARD,
        'body' => 'body',
      ]);
    expect($is_created->id() > 0)->true();
    expect($is_created->getErrors())->false();

    $newsletter = Newsletter::where('subject', 'new newsletter')
      ->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $is_updated = Newsletter::createOrUpdate(
      [
        'id' => $newsletter->id,
        'subject' => 'updated newsletter',
      ]);
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  public function testItCannotSetAnEmptyDeletedAt() {
    $this->newsletter->deleted_at = '';
    $newsletter = $this->newsletter->save();
    expect($newsletter->deleted_at)->equals('NULL');
  }

  public function testItCanBeFilteredBySegment() {
    // no filter
    $newsletters = Newsletter::filter('filterBy')->findArray();
    expect($newsletters)->count(1);

    // filter by segment
    $newsletters = Newsletter::filter('filterBy', [
      'filter' => [
        'segment' => $this->segment_1->id,
      ],
    ])->findArray();

    expect($newsletters)->count(1);
    expect($newsletters[0]['subject'])->equals($this->newsletter->subject);

    // remove all segment relations to newsletters
    NewsletterSegment::deleteMany();

    $newsletters = Newsletter::filter('filterBy', [
      'filter' => [
        'segment' => $this->segment_1->id,
      ]])->findArray();

    expect($newsletters)->isEmpty();
  }

  public function testItCanBeGrouped() {
    $newsletters = Newsletter::filter('groupBy', [
      'group' => 'all',
    ])->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', [
      'group' => 'trash',
    ])->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->trash();
    $newsletters = Newsletter::filter('groupBy', [
      'group' => 'trash',
    ])->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', [
      'group' => 'all',
    ])->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->restore();
    $newsletters = Newsletter::filter('groupBy', [
      'group' => 'all',
    ])->findArray();
    expect($newsletters)->count(1);
  }

  public function testItHasSearchFilter() {
    Newsletter::createOrUpdate(
      [
        'subject' => 'search for "pineapple"',
        'type' => Newsletter::TYPE_STANDARD,
        'body' => 'body',
      ]);
    $newsletter = Newsletter::filter('search', 'pineapple')
      ->findOne();
    expect($newsletter->subject)->contains('pineapple');
  }

  public function testItCanHaveOptions() {
    $newsletter_options = [
      'name' => 'Event',
      'newsletter_type' => Newsletter::TYPE_WELCOME,
    ];
    $option_field = NewsletterOptionField::create();
    $option_field->hydrate($newsletter_options);
    $option_field->save();
    $association = NewsletterOption::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->option_field_id = $option_field->id;
    $association->value = 'list';
    $association->save();
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_WELCOME)
      ->findOne($this->newsletter->id);
    expect($newsletter->Event)->equals($association->value);
  }

  public function testItGetsArchiveNewslettersForSegments() {
    // clear the DB
    $this->_after();

    $types = [
      Newsletter::TYPE_STANDARD,
      Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $newsletters = [];
    $sending_queues[] = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        [
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i],
        ]
      );
      $sending_queues[$i] = SendingTask::create();
      $sending_queues[$i]->newsletter_id = $newsletters[$i]->id;
      $sending_queues[$i]->status = SendingQueue::STATUS_COMPLETED;
      $sending_queues[$i]->save();
    }
    // set segment association for the last newsletter
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->newsletter_id = end($newsletters[1])->id;
    $newsletter_segment->segment_id = 123;
    $newsletter_segment->save();

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
    $sending_queues[] = [];
    for ($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        [
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i],
        ]
      );
      $sending_queues[$i] = SendingTask::create();
      $sending_queues[$i]->newsletter_id = $newsletters[$i]->id;
      $sending_queues[$i]->status = SendingQueue::STATUS_COMPLETED;
      $sending_queues[$i]->save();
    }
    // set the sending queue status of the first newsletter to null
    $sending_queues[0]->status = null;
    $sending_queues[0]->save();

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
    $notification_history = $this->newsletter->createNotificationHistory();
    expect($notification_history->hash)->notEquals($this->newsletter->hash);
    expect(strlen($notification_history->hash))
      ->equals(Security::HASH_LENGTH);
  }

  public function testItGetsQueueFromNewsletter() {
    expect($this->newsletter->queue()->findOne()->id)->equals($this->sending_queue->id);
  }

  public function testItCanBeRestored() {
    $this->newsletter->status = Newsletter::STATUS_SENT;
    $this->newsletter->trash();
    expect($this->newsletter->deleted_at)->notNull();
    $this->newsletter->restore();
    expect($this->newsletter->deleted_at)->equals('NULL');
    expect($this->newsletter->status)->equals(Newsletter::STATUS_SENT);

    // if the restored newsletter was trashed while in sending,
    // its status should be set to 'draft' to be able to send it again
    $this->newsletter->status = Newsletter::STATUS_SENDING;
    $this->newsletter->trash();
    $this->newsletter->restore();
    expect($this->newsletter->status)->equals(Newsletter::STATUS_DRAFT);
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
    expect(Newsletter::where('status', Newsletter::STATUS_SENDING)->findArray())->count(1);

    Newsletter::filter('bulkRestore');
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->isEmpty();
    expect(Newsletter::where('status', Newsletter::STATUS_SENDING)->findArray())->count(0);
  }

  public function testItDeletesSegmentAndQueueAssociationsWhenNewsletterIsDeleted() {
    $newsletter = $this->newsletter;

    // create multiple sending queues
    for ($i = 1; $i <= 5; $i++) {
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
    }

    // make sure relations exist
    expect(SendingQueue::where('newsletter_id', $newsletter->id)->findArray())->count(6);
    $newsletter_segments = NewsletterSegment::where('newsletter_id', $newsletter->id)->findArray();
    expect($newsletter_segments)->count(2);

    // delete newsletter and check that relations no longer exist
    $newsletter->delete();
    expect(SendingQueue::where('newsletter_id', $newsletter->id)->findArray())->isEmpty();
    $newsletter_segments = NewsletterSegment::where('newsletter_id', $newsletter->id)->findArray();
    expect($newsletter_segments)->isEmpty();
  }

  public function testItDeletesChildrenSegmentAndQueueAssociationsWhenParentNewsletterIsDeleted() {
    $parent_newsletter = $this->newsletter;
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parent_newsletter->id,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
      $newsletter_segment = NewsletterSegment::create();
      $newsletter_segment->newsletter_id = $newsletter->id;
      $newsletter_segment->segment_id = 1;
      $newsletter_segment->save();
    }

    // make sure relations exist
    // 1 parent newsletter/queues, 2 parent segments and 5 children queues/newsletters/segments
    expect(Newsletter::findArray())->count(6);
    expect(SendingQueue::findArray())->count(6);
    expect(NewsletterSegment::findArray())->count(7);

    // delete parent newsletter and check that relations no longer exist
    $parent_newsletter->delete();
    expect(Newsletter::findArray())->count(0);
    expect(SendingQueue::findArray())->count(0);
    expect(NewsletterSegment::findArray())->count(0);
  }

  public function testItTrashesQueueAssociationsWhenNewsletterIsTrashed() {
    // create multiple sending queues
    $newsletter = $this->newsletter;
    for ($i = 1; $i <= 5; $i++) {
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
    }
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // trash newsletter and check that relations are trashed
    $newsletter->trash();
    // 5 queues + 1 created in _before() method
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItTrashesChildrenQueueAssociationsWhenParentNewsletterIsTrashed() {
    $parent_newsletter = $this->newsletter;
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parent_newsletter->id,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
    }
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // trash parent newsletter and check that relations are trashed
    $parent_newsletter->trash();
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItRestoresTrashedQueueAssociationsWhenNewsletterIsRestored() {
    // create multiple sending queues
    $newsletter = $this->newsletter;
    for ($i = 1; $i <= 5; $i++) {
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->deleted_at = date('Y-m-d H:i:s');
      $sending_queue->save();
    }
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(5);

    // restore newsletter and check that relations are restored
    $newsletter->restore();
    // 5 queues + 1 created in _before() method
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
  }

  public function testItRestoresTrashedChildrenQueueAssociationsWhenParentNewsletterIsRestored() {
    // delete parent newsletter and sending queue
    $parent_newsletter = $this->newsletter;
    $parent_newsletter->deleted_at = date('Y-m-d H:i:s');
    $parent_newsletter->save();
    $parent_sending_queue = $this->sending_queue;
    $parent_sending_queue->deleted_at = date('Y-m-d H:i:s');
    $parent_sending_queue->save();

    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $parent_newsletter->id,
          'deleted_at' => date('Y-m-d H:i:s'),
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->deleted_at = date('Y-m-d H:i:s');
      $sending_queue->save();
    }
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);

    // restore parent newsletter and check that relations are restored
    $parent_newsletter->restore();
    // 1 parent and 5 children queues/newsletters
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
  }

  public function testItTrashesAllQueueAssociationsWhenNewslettersAreBulkTrashed() {
    // create multiple newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_STANDARD,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
    }
    // 5 queues/newsletters + 1 of each created in _before() method
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // bulk trash newsletters and check that relations are trashed
    Newsletter::bulkTrash(ORM::forTable(Newsletter::$_table));
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItTrashesAllChildrenQueueAssociationsWhenParentNewslettersAreBulkTrashed() {
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $this->newsletter->id,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
    }
    // 5 queues/newsletters + 1 of each created in _before() method
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);

    // bulk trash parent newsletters and check that relations are trashed
    Newsletter::bulkTrash(ORM::forTable(Newsletter::$_table));
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(6);
  }

  public function testItBulkRestoresTrashedQueueAssociationsWhenNewslettersAreBulkRestored() {
    // create multiple newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_STANDARD,
          'deleted_at' => date('Y-m-d H:i:s'),
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->deleted_at = date('Y-m-d H:i:s');
      $sending_queue->save();
    }
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(5);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(5);

    // bulk restore newsletters and check that relations are restored
    Newsletter::bulkRestore(ORM::forTable(Newsletter::$_table));
    // 5 queues/newsletters + 1 of each created in _before() method
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
  }

  public function testItBulkRestoresTrashedChildrenQueueAssociationsWhenParentNewslettersAreBulkRestored() {
    // create multiple children (post notification history) newsletters and sending queues
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $this->newsletter->id,
          'deleted_at' => date('Y-m-d H:i:s'),
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->deleted_at = date('Y-m-d H:i:s');
      $sending_queue->save();
    }
    expect(Newsletter::whereNotNull('deleted_at')->findArray())->count(5);
    expect(SendingQueue::whereNotNull('deleted_at')->findArray())->count(5);

    // bulk restore parent newsletters and check that relations are restored
    Newsletter::bulkRestore(ORM::forTable(Newsletter::$_table));
    // 1 parent and 5 queues/newsletters
    expect(Newsletter::whereNull('deleted_at')->findArray())->count(6);
    expect(SendingQueue::whereNull('deleted_at')->findArray())->count(6);
  }

  public function testItBulkDeletesSegmentAndQueueAssociationsWhenNewslettersAreBulkDeleted() {
    // create multiple newsletters, sending queues and newsletter segments
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_STANDARD,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
      $newsletter_segment = NewsletterSegment::create();
      $newsletter_segment->newsletter_id = $newsletter->id;
      $newsletter_segment->segment_id = 1;
      $newsletter_segment->save();
    }
    // 5 queues/newsletters + 1 of each created in _before() method
    expect(Newsletter::findArray())->count(6);
    expect(SendingQueue::findArray())->count(6);
    // 5 segment associations + 2 created in _before() method
    expect(NewsletterSegment::findArray())->count(7);

    // bulk delete newsletters and check that relations are deleted
    Newsletter::bulkDelete(ORM::forTable(Newsletter::$_table));
    expect(Newsletter::findArray())->count(0);
    expect(SendingQueue::findArray())->count(0);
    expect(NewsletterSegment::findArray())->count(0);
  }

  public function testItBulkDeletesChildrenSegmentAndQueueAssociationsWhenParentNewslettersAreBulkDeleted() {
    $this->_after();
    // create multiple children (post notification history) newsletters, sending queues and newsletter segments
    for ($i = 1; $i <= 5; $i++) {
      $newsletter = Newsletter::createOrUpdate(
        [
          'subject' => 'test',
          'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
          'parent_id' => $this->newsletter->id,
        ]
      );
      $sending_queue = SendingTask::create();
      $sending_queue->newsletter_id = $newsletter->id;
      $sending_queue->save();
      $newsletter_segment = NewsletterSegment::create();
      $newsletter_segment->newsletter_id = $newsletter->id;
      $newsletter_segment->segment_id = 1;
      $newsletter_segment->save();
    }
    // 5 queues/newsletters/segment associations
    expect(Newsletter::findArray())->count(5);
    expect(SendingQueue::findArray())->count(5);
    expect(NewsletterSegment::findArray())->count(5);

    // bulk delete newsletters and check that relations are deleted
    Newsletter::bulkDelete(ORM::forTable(Newsletter::$_table));
    expect(Newsletter::findArray())->count(0);
    expect(SendingQueue::findArray())->count(0);
    expect(NewsletterSegment::findArray())->count(0);
  }

  public function testItDuplicatesNewsletter() {
    $original_newsletter = $this->newsletter;
    $original_newsletter->status = Newsletter::STATUS_SENT;
    $original_newsletter->sent_at = $original_newsletter->deleted_at = $original_newsletter->created_at = $original_newsletter->updated_at = date( '2000-m-d H:i:s');
    $original_newsletter->save();
    $data = ['subject' => 'duplicate newsletter'];
    $duplicate_newsletter = $this->newsletter->duplicate($data);
    $duplicate_newsletter = Newsletter::findOne($duplicate_newsletter->id);
    // hash is different
    expect($duplicate_newsletter->hash)->notEquals($this->newsletter->hash);
    expect(strlen($duplicate_newsletter->hash))->equals(Security::HASH_LENGTH);
    // status is set to draft
    expect($duplicate_newsletter->status)->equals(Newsletter::STATUS_DRAFT);
    // sent at/delete at dates are null
    expect($duplicate_newsletter->sent_at)->null();
    expect($duplicate_newsletter->deleted_at)->null();
    // created at/updated at dates are different
    expect($duplicate_newsletter->created_at)->notEquals($original_newsletter->created_at);
    expect($duplicate_newsletter->updated_at)->notEquals($original_newsletter->updated_at);
    // body and subject are the same
    expect($duplicate_newsletter->body)->equals($original_newsletter->body);
    expect($duplicate_newsletter->subject)->equals($data['subject']);
  }

  public function testItCanQueryAutomaticEmailsByGroup() {
    $newsletter_1 = Newsletter::createOrUpdate(
      [
        'subject' => 'WooCommerce',
        'preheader' => 'Pre Header',
        'type' => Newsletter::TYPE_AUTOMATIC,
      ]
    );
    $newsletter_2 = Newsletter::createOrUpdate(
      [
        'subject' => 'Unicrons',
        'preheader' => 'Pre Header',
        'type' => Newsletter::TYPE_AUTOMATIC,
      ]
    );
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->hydrate(
      [
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
        'name' => 'group',
      ]
    );
    $newsletter_option_field->save();
    $newsletter_option_1 = NewsletterOption::create();
    $newsletter_option_1->hydrate(
      [
        'newsletter_id' => $newsletter_1->id,
        'option_field_id' => $newsletter_option_field->id,
        'value' => 'woocommerce',
      ]
    );
    $newsletter_option_1->save();
    $newsletter_option_2 = NewsletterOption::create();
    $newsletter_option_2->hydrate(
      [
        'newsletter_id' => $newsletter_2->id,
        'option_field_id' => $newsletter_option_field->id,
        'value' => 'unicorns',
      ]
    );
    $newsletter_option_2->save();
    $listings_data = [
      'params' => [
        'type' => Newsletter::TYPE_AUTOMATIC,
      ],
      'sort_by' => 'updated_at',
      'sort_order' => 'desc',
      'offset' => 0,
      'limit' => 20,
      'group' => 'all',
      'search' => '',
    ];

    // get "woocommerce" emails
    $listings_data['params']['group'] = 'woocommerce';
    $result = Newsletter::listingQuery($listings_data)->findMany();
    expect($result)->count(1);
    expect($result[0]->id)->equals($newsletter_1->id);

    // get "unicorn" emails
    $listings_data['params']['group'] = 'unicorns';
    $result = Newsletter::listingQuery($listings_data)->findMany();
    expect($result)->count(1);
    expect($result[0]->id)->equals($newsletter_2->id);

    // get all automatic emails
    unset($listings_data['params']['group']);
    $result = Newsletter::listingQuery($listings_data)->findMany();
    expect($result)->count(2);
    expect($result[0]->id)->equals($newsletter_1->id);
    expect($result[1]->id)->equals($newsletter_2->id);
  }

  public function testItGetsAndDecodesNewsletterOptionMetaField() {
    $newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'Test Option Meta Field',
        'preheader' => 'Pre Header',
        'type' => Newsletter::TYPE_AUTOMATIC,
      ]
    );
    $newsletter_option_field = NewsletterOptionField::create();
    $newsletter_option_field->hydrate(
      [
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
        'name' => 'meta',
      ]
    );
    $newsletter_option_field->save();
    $newsletter_option = NewsletterOption::create();
    $meta = ['some' => 'value'];
    $newsletter_option->hydrate(
      [
        'newsletter_id' => $newsletter->id,
        'option_field_id' => $newsletter_option_field->id,
        'value' => json_encode($meta),
      ]
    );
    $newsletter_option->save();

    // by default meta option does not exist on newsletter object
    expect($newsletter->getMeta())->isEmpty();

    // if meta option exists, it should be returned as an array
    $newsletter = Newsletter::filter('filterWithOptions', Newsletter::TYPE_AUTOMATIC)->findOne($newsletter->id);
    expect($newsletter->getMeta())->equals($meta);
  }

  public function testPausesTaskWhenNewsletterWithActivationIsDisabled() {
    $newsletters_with_activation = [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_WELCOME, Newsletter::TYPE_AUTOMATIC];
    foreach ($newsletters_with_activation as $type) {
      $newsletter = Newsletter::createOrUpdate([
        'type' => $type,
      ]);
      $task = ScheduledTask::createOrUpdate(['status' => ScheduledTask::STATUS_SCHEDULED]);
      SendingQueue::createOrUpdate([
        'newsletter_id' => $newsletter->id(),
        'task_id' => $task->id(),
      ]);
      $newsletter->setStatus(Newsletter::STATUS_DRAFT);
      $task_found = ScheduledTask::findOne($task->id());
      expect($task_found->status)->equals(ScheduledTask::STATUS_PAUSED);
    }
  }

  public function testUnpausesTaskWhenNewsletterWithActivationIsEnabled() {
    $newsletters_with_activation = [Newsletter::TYPE_NOTIFICATION, Newsletter::TYPE_WELCOME, Newsletter::TYPE_AUTOMATIC];
    foreach ($newsletters_with_activation as $type) {
      $newsletter = Newsletter::createOrUpdate([
        'type' => $type,
        'body' => '["x", "y"]',
      ]);
      $task = ScheduledTask::createOrUpdate([
        'status' => ScheduledTask::STATUS_PAUSED,
        'scheduled_at' => Carbon::createFromTimestamp(current_time('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
      ]);
      SendingQueue::createOrUpdate([
        'newsletter_id' => $newsletter->id(),
        'task_id' => $task->id(),
      ]);
      $newsletter->setStatus(Newsletter::STATUS_ACTIVE);
      $task_found = ScheduledTask::findOne($task->id());
      expect($task_found->status)->equals(ScheduledTask::STATUS_SCHEDULED);
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
