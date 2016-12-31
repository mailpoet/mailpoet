<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsUnsubscribes;

class NewsletterTest extends MailPoetTest {
  function _before() {
    $this->newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My Standard Newsletter',
      'preheader' => 'Pre Header',
      'type' => 'standard'
    ));

    $this->segment_1 = Segment::createOrUpdate(array(
      'name' => 'Segment 1'
    ));
    $association = NewsletterSegment::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->segment_id = $this->segment_1->id;
    $association->save();

    $this->segment_2 = Segment::createOrUpdate(array(
      'name' => 'Segment 2'
    ));
    $association = NewsletterSegment::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->segment_id = $this->segment_2->id;
    $association->save();
  }

  function testItCanBeCreated() {
    expect($this->newsletter->id() > 0)->true();
    expect($this->newsletter->getErrors())->false();
  }

  function testItHasASubject() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->subject)->equals($this->newsletter->subject);
  }

  function testItHasAType() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->type)->equals($this->newsletter->type);
  }

  function testItHasABody() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->body)->equals($this->newsletter->body);
  }

  function testItHasPreheader() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->preheader)->equals($this->newsletter->preheader);
  }

  function testItHasACreatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->created_at)->notNull();
  }

  function testItHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->updated_at)
      ->equals($newsletter->created_at);
  }

  function testItUpdatesTheUpdatedAtOnUpdate() {
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

  function testItCanBeQueued() {
    $queue = $this->newsletter->getQueue();
    expect($queue)->false();
    $sending_queue = SendingQueue::create();
    $sending_queue->newsletter_id = $this->newsletter->id;
    $sending_queue->save();
    $queue = $this->newsletter->getQueue();
    expect($queue->id() > 0)->true();
  }

  function testItCanHaveSegments() {
    $newsletter_segments = $this->newsletter->segments()->findArray();
    expect($newsletter_segments)->count(2);
    expect($newsletter_segments[0]['id'])->equals($this->segment_1->id);
    expect($newsletter_segments[0]['name'])->equals('Segment 1');
    expect($newsletter_segments[1]['id'])->equals($this->segment_2->id);
    expect($newsletter_segments[1]['name'])->equals('Segment 2');
  }

  function testItCanHaveDeletedSegments() {
    $this->segment_2->delete();
    $this->newsletter->withSegments(true);
    $newsletter_segments = $this->newsletter->segments;
    expect($newsletter_segments)->count(2);
    expect($newsletter_segments[0]['id'])->equals($this->segment_1->id);
    expect($newsletter_segments[0]['name'])->equals('Segment 1');
    expect($newsletter_segments[1]['id'])->equals($this->segment_2->id);
    expect($newsletter_segments[1]['name'])->contains('Deleted');
  }

  function testItCanHaveStatistics() {
    $newsletter = $this->newsletter;
    $sending_queue = SendingQueue::create();
    $sending_queue->newsletter_id = $this->newsletter->id;
    $sending_queue->save();

    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    ));

    $opens = StatisticsOpens::create();
    $opens->subscriber_id = $subscriber->id;
    $opens->newsletter_id = $this->newsletter->id;
    $opens->queue_id = $sending_queue->id;
    $opens->save();

    $opens = StatisticsClicks::create();
    $opens->subscriber_id = $subscriber->id;
    $opens->newsletter_id = $this->newsletter->id;
    $opens->queue_id = $sending_queue->id;
    $opens->link_id = 0;
    $opens->count = 0;
    $opens->save();

    $opens = StatisticsUnsubscribes::create();
    $opens->subscriber_id = $subscriber->id;
    $opens->newsletter_id = $this->newsletter->id;
    $opens->queue_id = $sending_queue->id;
    $opens->save();

    $newsletter->queue = $newsletter->getQueue()->asArray();
    $statistics = $newsletter->getStatistics( $sending_queue->id);
    expect($statistics['opened'])->equals(1);
    expect($statistics['clicked'])->equals(1);
    expect($statistics['unsubscribed'])->equals(1);
  }

  function testItCanCreateOrUpdate() {
    $is_created = Newsletter::createOrUpdate(
      array(
        'subject' => 'new newsletter',
        'type' => 'standard',
        'body' => 'body'
      ));
    expect($is_created->id() > 0)->true();
    expect($is_created->getErrors())->false();

    $newsletter = Newsletter::where('subject', 'new newsletter')
      ->findOne();
    expect($newsletter->subject)->equals('new newsletter');

    $is_updated = Newsletter::createOrUpdate(
      array(
        'id' => $newsletter->id,
        'subject' => 'updated newsletter'
      ));
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter->subject)->equals('updated newsletter');
  }

  function testItCannotSetAnEmptyDeletedAt() {
    $this->newsletter->deleted_at = '';
    $newsletter = $this->newsletter->save();
    expect($newsletter->deleted_at)->equals('NULL');
  }

  function testItCanBeFilteredBySegment() {
    // no filter
    $newsletters = Newsletter::filter('filterBy')->findArray();
    expect($newsletters)->count(1);

    // filter by segment
    $newsletters = Newsletter::filter('filterBy', array(
      'filter' => array(
        'segment' => $this->segment_1->id
      )
    ))->findArray();

    expect($newsletters)->count(1);
    expect($newsletters[0]['subject'])->equals($this->newsletter->subject);

    // remove all segment relations to newsletters
    NewsletterSegment::deleteMany();

    $newsletters = Newsletter::filter('filterBy', array(
      'filter' => array(
        'segment' => $this->segment_1->id
      )))->findArray();

    expect($newsletters)->isEmpty();
  }

  function testItCanBeGrouped() {
    $newsletters = Newsletter::filter('groupBy', array(
      'group' => 'all'
    ))->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', array(
      'group' => 'trash'
    ))->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->trash();
    $newsletters = Newsletter::filter('groupBy', array(
      'group' => 'trash'
    ))->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', array(
      'group' => 'all'
    ))->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->restore();
    $newsletters = Newsletter::filter('groupBy', array(
      'group' => 'all'
    ))->findArray();
    expect($newsletters)->count(1);
  }

  function testItHasSearchFilter() {
    Newsletter::createOrUpdate(
      array(
        'subject' => 'search for "pineapple"',
        'type' => 'standard',
        'body' => 'body'
      ));
    $newsletter = Newsletter::filter('search', 'pineapple')
      ->findOne();
    expect($newsletter->subject)->contains('pineapple');
  }

  function testItCanHaveOptions() {
    $newsletter_options = array(
      'name' => 'Event',
      'newsletter_type' => 'welcome',
    );
    $option_field = NewsletterOptionField::create();
    $option_field->hydrate($newsletter_options);
    $option_field->save();
    $association = NewsletterOption::create();
    $association->newsletter_id = $this->newsletter->id;
    $association->option_field_id = $option_field->id;
    $association->value = 'list';
    $association->save();
    $newsletter = Newsletter::filter('filterWithOptions')
      ->findOne($this->newsletter->id);
    expect($newsletter->Event)->equals($association->value);
  }

  function testItGetsArchiveNewslettersForSegments() {
    // clear the DB
    $this->_after();

    $types = array(
      Newsletter::TYPE_STANDARD,
      Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $newsletters = array();
    $sending_queues[] = array();
    for($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        array(
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i]
        )
      );
      $sending_queues[$i] = SendingQueue::create();
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
    $results = Newsletter::getArchives(array(123));
    expect($results)->count(1);
    expect($results[0]->id)->equals($newsletters[1]->id);
    expect($results[0]->type)->equals(Newsletter::TYPE_NOTIFICATION_HISTORY);
  }

  function testItGetsAllArchiveNewsletters() {
    // clear the DB
    $this->_after();

    $types = array(
      Newsletter::TYPE_STANDARD,
      Newsletter::TYPE_STANDARD, // should be returned
      Newsletter::TYPE_WELCOME,
      Newsletter::TYPE_NOTIFICATION,
      Newsletter::TYPE_NOTIFICATION_HISTORY, // should be returned
      Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $newsletters = array();
    $sending_queues[] = array();
    for($i = 0; $i < count($types); $i++) {
      $newsletters[$i] = Newsletter::createOrUpdate(
        array(
          'subject' => 'My Standard Newsletter',
          'preheader' => 'Pre Header',
          'type' => $types[$i]
        )
      );
      $sending_queues[$i] = SendingQueue::create();
      $sending_queues[$i]->newsletter_id = $newsletters[$i]->id;
      $sending_queues[$i]->status = SendingQueue::STATUS_COMPLETED;
      $sending_queues[$i]->save();
    }
    // set teh sending queue status of the first newsletter to null
    $sending_queues[0]->status = null;
    $sending_queues[0]->save();

    // trash the last newsletter
    end($newsletters)->trash();

    expect(Newsletter::findMany())->count(6);

    // archives return only:
    // 1. STANDARD and NOTIFICATION HISTORY newsletters
    // 2. active newsletters (i.e., not trashed)
    // 3. with sending queue records that are COMPLETED
    $results = Newsletter::getArchives();
    expect($results)->count(2);
    expect($results[0]->id)->equals($newsletters[1]->id);
    expect($results[0]->type)->equals(Newsletter::TYPE_STANDARD);
    expect($results[1]->id)->equals($newsletters[4]->id);
    expect($results[1]->type)->equals(Newsletter::TYPE_NOTIFICATION_HISTORY);
  }

  function testItGeneratesHashOnNewsletterSave() {
    expect(strlen($this->newsletter->hash))
      ->equals(Newsletter::NEWSLETTER_HASH_LENGTH);
  }

  function testItRegeneratesHashOnNewsletterDuplication() {
    $duplicate_newsletter = $this->newsletter->duplicate();
    expect($duplicate_newsletter->hash)->notEquals($this->newsletter->hash);
    expect(strlen($duplicate_newsletter->hash))
      ->equals(Newsletter::NEWSLETTER_HASH_LENGTH);
  }

  function testItRegeneratesHashOnNotificationHistoryCreation() {
    $notification_history = $this->newsletter->createNotificationHistory();
    expect($notification_history->hash)->notEquals($this->newsletter->hash);
    expect(strlen($notification_history->hash))
      ->equals(Newsletter::NEWSLETTER_HASH_LENGTH);
  }

  function testItGetsQueueFromNewsletter() {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $this->newsletter->id;
    $queue->save();
    expect($this->newsletter->queue()->findOne()->id)->equals($queue->id);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsUnsubscribes::$_table);
  }
}