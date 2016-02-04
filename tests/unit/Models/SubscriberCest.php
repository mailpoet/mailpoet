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
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
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

  function itHasStatusDefaultStatusOfUnconfirmed() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->status)->equals('unconfirmed');
  }

  function itCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    $subscriber->status = 'subscribed';
    $subscriber->save();

    expect($subscriber->id() > 0)->true();
    expect($subscriber->getErrors())->false();
    $subscriber_updated = Subscriber::where('email', $this->data['email'])
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
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unconfirmed');
    }
    $subscribers = Subscriber::filter('groupBy', 'subscribed')
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('subscribed');
    }
    $subscribers = Subscriber::filter('groupBy', 'unsubscribed')
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals('unsubscribed');
    }
  }

  function itCanHaveSegment() {
    $segment = Segment::createOrUpdate(array(
      'name' => 'some name'
    ));
    expect($segment->getErrors())->false();

    $association = SubscriberSegment::create();
    $association->subscriber_id = $this->subscriber->id;
    $association->segment_id = $segment->id;
    $association->save();

    $subscriber = Subscriber::findOne($this->subscriber->id);

    $subscriber_segment = $subscriber->segments()->findOne();
    expect($subscriber_segment->id)->equals($segment->id);
  }

  function itCanHaveCustomFields() {
    $custom_field = CustomField::createOrUpdate(array(
      'name' => 'DOB',
      'type' => 'date',
    ));

    $association = SubscriberCustomField::create();
    $association->subscriber_id = $this->subscriber->id;
    $association->custom_field_id = $custom_field->id;
    $association->value = '12/12/2012';
    $association->save();

    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->findOne($this->subscriber->id);
    expect($subscriber->DOB)->equals($association->value);
  }

  function itCanFilterCustomFields() {
    $cf_city = CustomField::createOrUpdate(array(
      'name' => 'City',
      'type' => 'text'
    ));

    SubscriberCustomField::createOrUpdate(array(
      'subscriber_id' => $this->subscriber->id,
      'custom_field_id' => $cf_city->id,
      'value' => 'Paris'
    ));

    $cf_country = CustomField::createOrUpdate(array(
      'name' => 'Country',
      'type' => 'text'
    ));

    SubscriberCustomField::createOrUpdate(array(
      'subscriber_id' => $this->subscriber->id,
      'custom_field_id' => $cf_country->id,
      'value' => 'France'
    ));

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
    expect($result->id() > 0)->true();
    expect($result->getErrors())->false();

    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    expect($record->first_name)->equals($data['first_name']);
    expect($record->last_name)->equals($data['last_name']);
    $record->last_name = 'Mailer';
    $result = Subscriber::createOrUpdate($record->asArray());
    expect($result)->notEquals(false);
    expect($result->getValidationErrors())->isEmpty();
    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    expect($record->last_name)->equals('Mailer');
  }

  function itCanCreateOrUpdateMultipleRecords() {
    ORM::forTable(Subscriber::$_table)->deleteMany();
    $columns = array(
      'first_name',
      'last_name',
      'email'
    );
    $values = array(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com'
      ),
      array(
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com'
      )
    );
    Subscriber::createMultiple($columns, $values);
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[1]['email'])->equals($values[1]['email']);

    $values[0]['first_name'] = 'John';
    Subscriber::updateMultiple($columns, $values);
    $subscribers = Subscriber::findArray();
     expect($subscribers[0]['first_name'])->equals($values[0]['first_name']);
  }

  function itCanSubscribe() {
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(array('name' => 'List #2'));
    $segment2->save();

    $subscriber = Subscriber::subscribe(
      $this->data,
      array($segment->id(), $segment2->id())
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    expect($subscriber->email)->equals($this->data['email']);
    expect($subscriber->first_name)->equals($this->data['first_name']);
    expect($subscriber->last_name)->equals($this->data['last_name']);
    expect($subscriber->status)->equals('subscribed');
    expect($subscriber->deleted_at)->equals(null);
  }

  function itCanBeAddedToSegments() {
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(array('name' => 'List #2'));
    $segment2->save();

    $this->subscriber->addToSegments(array($segment->id(), $segment2->id()));
    $subscriber_segments = $this->subscriber->segments()->findArray();

    expect($this->subscriber->segments()->count())->equals(2);
    expect($subscriber_segments[0]['name'])->equals('List #1');
    expect($subscriber_segments[1]['name'])->equals('List #2');
  }

  function itCanBeUpdatedByEmail() {
    $subscriber_updated = Subscriber::createOrUpdate(array(
      'email' => $this->data['email'],
      'first_name' => 'JoJo',
      'last_name' => 'DoDo'
    ));

    expect($this->subscriber->id())->equals($subscriber_updated->id());

    $subscriber = Subscriber::findOne($this->subscriber->id());
    expect($subscriber->email)->equals($this->data['email']);
    expect($subscriber->first_name)->equals('JoJo');
    expect($subscriber->last_name)->equals('DoDo');
  }

  function itCanSetCustomField() {
    $custom_field = CustomField::createOrUpdate(array(
      'name' => 'Date of Birth',
      'type' => 'date'
    ));

    expect($custom_field->id() > 0)->true();

    $value = array(
      'year' => 1984,
      'month' => 3,
      'day' => 9
    );

    $subscriber = Subscriber::findOne($this->subscriber->id());
    $subscriber->setCustomField($custom_field->id(), $value);

    $subscriber = $subscriber->withCustomFields()->asArray();

    expect($subscriber['cf_'.$custom_field->id()])->equals(
      mktime(0, 0, 0, $value['month'], $value['day'], $value['year'])
    );
  }

  function itCanGetCustomField() {
    $subscriber = Subscriber::findOne($this->subscriber->id());

    expect($subscriber->getCustomField(9999, 'default_value'))
      ->equals('default_value');

    $custom_field = CustomField::createOrUpdate(array(
      'name' => 'Custom field: text input',
      'type' => 'input'
    ));

    $subscriber->setCustomField($custom_field->id(), 'non_default_value');

    expect($subscriber->getCustomField($custom_field->id(), 'default_value'))
      ->equals('non_default_value');
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