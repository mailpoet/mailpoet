<?php

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SegmentCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'some name',
      'description' => 'some description'
    );
    $this->subscribers_data = array(
      array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'status' => 'unsubscribed',
        'email' => 'john@mailpoet.com'
      ),
      array(
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'status' => 'subscribed',
        'email' => 'mike@maipoet.com'
      )
    );
    $this->newsletters_data = array(
      array(
        'subject' => 'My first newsletter',
        'type' => 'standard'
      ),
      array(
        'subject' => 'My second newsletter',
        'type' => 'standard'
      )
    );
    $this->segment = Segment::createOrUpdate($this->data);
  }

  function itCanBeCreated() {
    expect($this->segment->id() > 0)->true();
    expect($this->segment->getErrors())->false();
  }

  function itCanHaveName() {
    expect($this->segment->name)->equals($this->data['name']);
  }

  function nameMustBeUnique() {
    $segment = Segment::create();
    $segment->hydrate($this->data);
    $result = $segment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  function itCanHaveDescription() {
    expect($this->segment->description)->equals($this->data['description']);
  }

  function itHasToBeValid() {
    $invalid_segment = Segment::create();

    $result = $invalid_segment->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
  }

  function itHasACreatedAtOnCreation() {
    $segment = Segment::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($segment->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $segment = Segment::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($segment->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $segment = Segment::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $segment->created_at;
    $segment->name = 'new name';
    $segment->save();
    expect($old_created_at)->equals($segment->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $segment = Segment::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $segment->name = 'new name';
    $segment->save();
    $time_difference = strtotime($segment->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function itCanCreateOrUpdate() {
    $is_created = Segment::createOrUpdate(array(
      'name' => 'new list'
    ));
    expect($is_created->id() > 0)->true();
    expect($is_created->getErrors())->false();

    $segment = Segment::where('name', 'new list')
      ->findOne();
    expect($segment->name)->equals('new list');

    $is_updated = Segment::createOrUpdate(
      array(
        'id' => $segment->id,
        'name' => 'updated list'
      ));
    $segment = Segment::where('name', 'updated list')
      ->findOne();
    expect($segment->name)->equals('updated list');
  }

  function itCanHaveManySubscribers() {
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $subscribers = $segment->subscribers()
      ->findArray();

    expect(count($subscribers))->equals(2);
  }

  function itCanHaveManyNewsletters() {
    foreach($this->newsletters_data as $newsletter_data) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($newsletter_data);
      $newsletter->save();
      $association = NewsletterSegment::create();
      $association->newsletter_id = $newsletter->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segment = Segment::findOne($this->segment->id);
    $newsletters = $segment->newsletters()
      ->findArray();

    expect(count($newsletters))->equals(2);
  }

  function itCanGetSegmentsWithSubscriberCount() {
    foreach($this->subscribers_data as $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }
    $segment = Segment::getSegmentsWithSubscriberCount();
    expect($segment[0]['subscribers'])->equals(2);
  }

  function itCanGetSegmentsForExport() {
    foreach($this->subscribers_data as $index => $subscriber_data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriber_data);
      $subscriber->save();
      if(!$index) {
        $association = SubscriberSegment::create();
        $association->subscriber_id = $subscriber->id;
        $association->segment_id = $this->segment->id;
        $association->save();
      }
    }
    $segments = Segment::getSegmentsForExport();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals('Not In List');
    $segments = Segment::getSegmentsForExport($withConfirmedSubscribers = true);
    expect(count($segments))->equals(1);
  }

  function _after() {
    ORM::forTable(Segment::$_table)
      ->deleteMany();
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
    ORM::forTable(Newsletter::$_table)
      ->deleteMany();
    ORM::forTable(NewsletterSegment::$_table)
      ->deleteMany();
  }
}
