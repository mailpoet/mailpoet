<?php
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;

class SubscriberCest {

  function _before() {
    $this->data = array(
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    );
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate($this->data);
    $this->saved = $this->subscriber->save();
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasFirstName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->first_name)
      ->equals($this->data['first_name']);
  }

  function itHasLastName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->last_name)
      ->equals($this->data['last_name']);
  }

  function itHasEmail() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->email)
      ->equals($this->data['email']);
  }

  function emailMustBeUnique() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->hydrate($this->data);
    $saved = $conflict_subscriber->save();
    expect($saved)->notEquals(true);
  }

  function itHasStatus() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->status)->equals('unconfirmed');
  }

  function itCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->data['email'])
      ->findOne();
    $subscriber->status = 'subscribed';
    expect($subscriber->save())->equals(true);
    $subscriber_updated = Subscriber::where(
      'email',
      $this->data['email']
    )
      ->findOne();
    expect($subscriber_updated->status)->equals('subscribed');
  }

  function itHasSearchFilter() {
    $subscriber = Subscriber::filter('search', 'john')
      ->findOne();
    expect($subscriber->first_name)->equals($this->data['first_name']);
    $subscriber = Subscriber::filter('search', 'mailer')
      ->findOne();
    expect($subscriber->last_name)->equals($this->data['last_name']);
    $subscriber = Subscriber::filter('search', 'mailpoet')
      ->findOne();
    expect($subscriber->email)->equals($this->data['email']);
  }

  function itHasGroupFilter() {
    $subscribers = Subscriber::filter('groupBy', 'unconfirmed')
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unconfirmed');
    }
    $subscribers = Subscriber::filter('groupBy', 'subscribed')
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals('subscribed');
    }
    $subscribers = Subscriber::filter('groupBy', 'unsubscribed')
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unsubscribed');
    }
  }

  function itCanHaveSegment() {
    $segmentData = array(
      'name' => 'some name'
    );
    $segment = Segment::create();
    $segment->hydrate($segmentData);
    $segment->save();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $this->subscriber->id;
    $association->segment_id = $segment->id;
    $association->save();
    $subscriber = Subscriber::findOne($this->subscriber->id);
    $subscriberSegment = $subscriber->segments()
      ->findOne();
    expect($subscriberSegment->id)->equals($segment->id);
  }

  function itCanHaveCustomFields() {
    $customFieldData = array(
      'name' => 'DOB',
      'type' => 'date',
    );
    $customField = CustomField::create();
    $customField->hydrate($customFieldData);
    $customField->save();
    $association = SubscriberCustomField::create();
    $association->subscriber_id = $this->subscriber->id;
    $association->custom_field_id = $customField->id;
    $association->value = '12/12/2012';
    $association->save();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->findOne($this->subscriber->id);
    expect($subscriber->DOB)->equals($association->value);
  }
  
  function itCanFilterCustomFields() {
    $customFieldData = array(
      array(
        'name' => 'City',
        'type' => 'text',
      ),
      array(
        'name' => 'Country',
        'type' => 'text',
      )
    );
    foreach ($customFieldData as $data) {
      $customField = CustomField::create();
      $customField->hydrate($data);
      $customField->save();
      $createdCustomFields[] = $customField->asArray();
    }
    $subscriberCustomFieldData = array(
      array(
        'subscriber_id' => $this->subscriber->id,
        'custom_field_id' => $createdCustomFields[0]['id'],
        'value' => 'Paris'
      ),
      array(
        'subscriber_id' => $this->subscriber->id,
        'custom_field_id' => $createdCustomFields[1]['id'],
        'value' => 'France'
      )
    );
    foreach ($subscriberCustomFieldData as $data) {
      $association = SubscriberCustomField::create();
      $association->hydrate($data);
      $association->save();
      $createdAssociations[] = $association->asArray();
    }
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Paris'
        )
      ))
      ->findArray();
    expect(empty($subscriber))->false();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Paris'
        ),
        array(
          'name' => 'Country',
          'value' => 'France'
        )
      ))
      ->findArray();
    expect(empty($subscriber))->false();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Paris'
        ),
        array(
          'name' => 'Country',
          'value' => 'Russia'
        )
      ), 'OR')
      ->findArray();
    expect(empty($subscriber))->false();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'is'
        )
      ), 'AND', 'LIKE')
      ->findArray();
    expect(empty($subscriber))->false();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Moscow'
        )
      ))
      ->findArray();
    expect(empty($subscriber))->true();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Paris'
        ),
        array(
          'name' => 'Country',
          'value' => 'Russia'
        )
      ))
      ->findArray();
    expect(empty($subscriber))->true();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'Moscow'
        ),
        array(
          'name' => 'Country',
          'value' => 'Russia'
        )
      ), 'OR')
      ->findArray();
    expect(empty($subscriber))->true();
    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->filter('filterSearchCustomFields', array(
        array(
          'name' => 'City',
          'value' => 'zz'
        )
      ), 'AND', 'LIKE')
      ->findArray();
    expect(empty($subscriber))->true();
  }

  function itCanCreateOrUpdate() {
    $data = array(
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    );
    $result = Subscriber::createOrUpdate($data);
    expect($result)->equals(true);
    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    expect($record->first_name)->equals($data['first_name']);
    expect($record->last_name)->equals($data['last_name']);
    $record->last_name = 'Mailer';
    $result = Subscriber::createOrUpdate($record->asArray());
    expect($result)->equals(true);
    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    expect($record->last_name)->equals('Mailer');
  }

  function _after() {
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(Segment::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
    ORM::forTable(CustomField::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
  }
}