<?php

use MailPoet\Models\PivotSubscriberList;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberList;

class SubscriberListCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'some name',
    );

    $this->list = SubscriberList::create();
    $this->list->hydrate($this->data);
    $this->saved = $this->list->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasToBeValid() {
    expect($this->saved)->equals(true);
    $empty_model = SubscriberList::create();
    expect($empty_model->save())->equals(false);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(2);
  }

  function itHasACreatedAtOnCreation() {
    $list = SubscriberList::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($list->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $list = SubscriberList::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($list->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $list = SubscriberList::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $list->created_at;
    $list->name = 'new name';
    $list->save();
    expect($old_created_at)->equals($list->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $list = SubscriberList::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $list->name = 'new name';
    $list->save();
    $time_difference = strtotime($list->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function itCanCreateOrUpdate() {
    $data = array(
      'name' => 'some other new name'
    );
    $createNewRecord = SubscriberList::createOrUpdate($data);

    $data = array(
      'name' => $this->data['name'],
      'name_updated' => 'updated name',
    );
    $updateExistingRecord = SubscriberList::createOrUpdate($data);

    $allRecords = SubscriberList::find_array();
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
      $association = PivotSubscriberList::create();
      $association->subscriber_id = $subscriber->id;
      $association->list_id = $this->list->id;
      $association->save();
    }

    $list = SubscriberList::find_one($this->list->id);
    $subscribers = $list->subscribers()
      ->find_array();
    expect(count($subscribers))->equals(2);
  }

  function _after() {
    ORM::for_table(SubscriberList::$_table)
      ->delete_many();
    ORM::for_table(Subscriber::$_table)
      ->delete_many();
    ORM::for_table(PivotSubscriberList::$_table)
      ->delete_many();
  }


}
