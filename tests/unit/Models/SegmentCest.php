<?php

use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;

class SegmentCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'some name',
      'description' => 'some description'
    );

    $this->segment = Segment::create();
    $this->segment->hydrate($this->data);
    $this->saved = $this->segment->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itCanHaveName() {
    expect($this->segment->name)->equals($this->data['name']);
  }

  function nameMustBeUnique() {
    $segment = Segment::create();
    $segment->hydrate($this->data);
    expect($segment->save())->contains('Duplicate');
  }

  function itCanHaveDescription() {
    expect($this->segment->description)->equals($this->data['description']);
  }

  function itHasToBeValid() {
    expect($this->saved)->equals(true);
    $empty_model = Segment::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(1);
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
    expect($is_created)->notEquals(false);
    expect($is_created->getValidationErrors())->isEmpty();

    $segment = Segment::where('name', 'new list')->findOne();
    expect($segment->name)->equals('new list');

    $is_updated = Segment::createOrUpdate(array(
      'id' => $segment->id,
      'name' => 'updated list'
    ));
    $segment = Segment::where('name', 'updated list')->findOne();
    expect($segment->name)->equals('updated list');
  }

  function itCanHaveManySubscribers() {
    $subscribersData = array(
      array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'email' => 'john@mailpoet.com'
      ),
      array(
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'email' => 'mike@maipoet.com'
      )
    );

    foreach($subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }

    $segment = Segment::findOne($this->segment->id);
    $subscribers = $segment->subscribers()->findArray();

    expect(count($subscribers))->equals(2);
  }

  function itCanHaveManyNewsletters() {
    $newslettersData = array(
      array(
        'subject' => 'My first newsletter'
      ),
      array(
        'subject' => 'My second newsletter'
      )
    );

    foreach($newslettersData as $newsletterData) {
      $newsletter = Newsletter::create();
      $newsletter->hydrate($newsletterData);
      $newsletter->save();
      $association = NewsletterSegment::create();
      $association->newsletter_id = $newsletter->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }

    $segment = Segment::findOne($this->segment->id);
    $newsletters = $segment->newsletters()->findArray();

    expect(count($newsletters))->equals(2);
  }

  function _after() {
    ORM::forTable(Segment::$_table)
      ->deleteMany();
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
  }


}
