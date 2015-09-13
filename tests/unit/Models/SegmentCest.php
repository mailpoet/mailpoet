<?php

use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;

class SegmentCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'some name',
    );

    $this->segment = Segment::create();
    $this->segment->hydrate($this->data);
    $this->saved = $this->segment->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasToBeValid() {
    expect($this->saved)->equals(true);
    $empty_model = Segment::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(2);
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
    $data = array(
      'name' => 'some other new name'
    );
    $createNewRecord = Segment::createOrUpdate($data);

    $data = array(
      'name' => $this->data['name'],
      'name_updated' => 'updated name',
    );
    $updateExistingRecord = Segment::createOrUpdate($data);

    $allRecords = Segment::find_array();
    expect(count($allRecords))->equals(2);
    expect($allRecords[0]['name'])->equals($data['name_updated']);
  }

  function itCanHaveMultipleSubscribers() {
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
    foreach ($subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $this->segment->id;
      $association->save();
    }

    $segment = Segment::find_one($this->segment->id);
    $subscribers = $segment->subscribers()
      ->find_array();
    expect(count($subscribers))->equals(2);
  }

  function _after() {
    ORM::for_table(Segment::$_table)
      ->delete_many();
    ORM::for_table(Subscriber::$_table)
      ->delete_many();
    ORM::for_table(SubscriberSegment::$_table)
      ->delete_many();
  }


}
