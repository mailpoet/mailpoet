<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\NewsletterOption;

class NewsletterCest {
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

  function itCanBeCreated() {
    expect($this->newsletter->id() > 0)->true();
    expect($this->newsletter->getErrors())->false();
  }

  function itHasASubject() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->subject)->equals($this->newsletter->subject);
  }

  function itHasAType() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->type)->equals($this->newsletter->type);
  }

  function itHasABody() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->body)->equals($this->newsletter->body);
  }

  function itHasPreheader() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->preheader)->equals($this->newsletter->preheader);
  }

  function itHasACreatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->created_at)->notNull();
    expect($newsletter->created_at)->notEquals('0000-00-00 00:00:00');
  }

  function itHasAnUpdatedAtOnCreation() {
    $newsletter = Newsletter::findOne($this->newsletter->id);
    expect($newsletter->updated_at)
      ->equals($newsletter->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
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

  function itCanBeQueued() {
    $queue = $this->newsletter->getQueue();
    expect($queue)->false();

    $sending_queue = SendingQueue::create();
    $sending_queue->newsletter_id = $this->newsletter->id;
    $sending_queue->save();

    $queue = $this->newsletter->getQueue();
    expect($queue->id() > 0)->true();
  }

  function itCanHaveSegments() {
    $newsletter_segments = $this->newsletter->segments()->findArray();
    expect($newsletter_segments)->count(2);
    expect($newsletter_segments[0]['id'])->equals($this->segment_1->id);
    expect($newsletter_segments[0]['name'])->equals('Segment 1');
    expect($newsletter_segments[1]['id'])->equals($this->segment_2->id);
    expect($newsletter_segments[1]['name'])->equals('Segment 2');
  }

  function itCanCreateOrUpdate() {
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

  function itCannotSetAnEmptyDeletedAt() {
    $this->newsletter->deleted_at = '';
    $newsletter = $this->newsletter->save();
    expect($newsletter->deleted_at)->equals('NULL');
  }

  function itCanBeFilteredBySegment() {
    // no filter
    $newsletters = Newsletter::filter('filterBy')->findArray();
    expect($newsletters)->count(1);

    // filter by segment
    $newsletters = Newsletter::filter('filterBy', array(
      'segment' => $this->segment_1->id
    ))->findArray();

    expect($newsletters)->count(1);
    expect($newsletters[0]['subject'])->equals($this->newsletter->subject);

    // remove all segment relations to newsletters
    NewsletterSegment::deleteMany();

    $newsletters = Newsletter::filter('filterBy', array(
      'segment' => $this->segment_1->id
    ))->findArray();

    expect($newsletters)->isEmpty();
  }

  function itCanBeGrouped() {
    $newsletters = Newsletter::filter('groupBy', 'all')->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', 'trash')->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->trash();
    $newsletters = Newsletter::filter('groupBy', 'trash')->findArray();
    expect($newsletters)->count(1);

    $newsletters = Newsletter::filter('groupBy', 'all')->findArray();
    expect($newsletters)->count(0);

    $this->newsletter->restore();
    $newsletters = Newsletter::filter('groupBy', 'all')->findArray();
    expect($newsletters)->count(1);
  }

  function itHasSearchFilter() {
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

  function itCanHaveOptions() {
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

  function itCanFilterOptions() {
    $newsletter_options = array(
      array(
        'name' => 'Event',
        'newsletter_type' => 'welcome',
      ),
      array(
        'name' => 'List',
        'newsletter_type' => 'welcome',
      )
    );
    foreach ($newsletter_options as $data) {
      $option_field = NewsletterOptionField::create();
      $option_field->hydrate($data);
      $option_field->save();
      $createdOptionFields[] = $option_field->asArray();
    }
    $newsletter_options = array(
      array(
        'newsletter_id' => $this->newsletter->id,
        'option_field_id' => $createdOptionFields[0]['id'],
        'value' => 'list'
      ),
      array(
        'newsletter_id' => $this->newsletter->id,
        'option_field_id' => $createdOptionFields[1]['id'],
        'value' => '1'
      )
    );
    foreach($newsletter_options as $data) {
      $association = NewsletterOption::create();
      $association->hydrate($data);
      $association->save();
    }

    $newsletter = Newsletter::filter('filterWithOptions')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'Event',
          'value' => 'list'
        )
      ))
      ->findArray();
    expect(empty($newsletter))->false();
    $newsletter = Newsletter::filter('filterWithOptions')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'Event',
          'value' => 'list'
        ),
        array(
          'name' => 'List',
          'value' => '1'
        )
      ))
      ->findArray();
    expect(empty($newsletter))->false();
    $newsletter = Newsletter::filter('filterWithOptions')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'Event',
          'value' => 'list'
        ),
        array(
          'name' => 'List',
          'value' => '2'
        )
      ))
      ->findArray();
    expect(empty($newsletter))->true();
  }

  function _after() {
    NewsletterOption::deleteMany();
    NewsletterOptionField::deleteMany();
    Newsletter::deleteMany();
    Segment::deleteMany();
    NewsletterSegment::deleteMany();
    SendingQueue::deleteMany();
  }
}
