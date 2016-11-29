<?php

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;

class SubscriberTest extends MailPoetTest {

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

  function testItCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function testItHasFirstName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->first_name)
      ->equals($this->data['first_name']);
  }

  function testItHasLastName() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->last_name)
      ->equals($this->data['last_name']);
  }

  function testItHasEmail() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->email)
      ->equals($this->data['email']);
  }

  function testItShouldSetErrors() {
    // model validation
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'email' => 'invalid_email'
    ));
    $subscriber->save();
    $errors = $subscriber->getErrors();
    expect($errors)->contains("Your email address is invalid!");

    // pdo error
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'email' => 'test@test.com',
      'invalid_column' => true
    ));
    $subscriber->save();
    $errors = $subscriber->getErrors();
    expect($errors[0])->contains("Unknown column 'invalid_column' in 'field list'");
  }

  function emailMustBeUnique() {
    $conflict_subscriber = Subscriber::create();
    $conflict_subscriber->hydrate($this->data);
    $saved = $conflict_subscriber->save();
    expect($saved)->notEquals(true);
  }

  function testItHasStatusDefaultStatusOfUnconfirmed() {
    $subscriber =
      Subscriber::where('email', $this->data['email'])
        ->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  function testItCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->data['email'])->findOne();
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    expect($subscriber->id() > 0)->true();
    expect($subscriber->getErrors())->false();
    $subscriber_updated = Subscriber::where('email', $this->data['email'])
      ->findOne();
    expect($subscriber_updated->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItHasSearchFilter() {
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

  function testItHasGroupFilter() {
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNCONFIRMED)
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    }

    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_SUBSCRIBED)
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNSUBSCRIBED)
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_BOUNCED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_BOUNCED)
      ->findMany();
    foreach($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_BOUNCED);
    }
  }

  function testItCanHaveSegment() {
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

  function testItCanHaveCustomFields() {
    $custom_field = CustomField::createOrUpdate(array(
      'name' => 'DOB',
      'type' => 'date'
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

  function testItCanCreateSubscriberWithCustomFields() {
    $custom_field = CustomField::createOrUpdate(array(
      'name' => 'City',
      'type' => 'text'
    ));

    $custom_field_2 = CustomField::createOrUpdate(array(
      'name' => 'Birthday',
      'type' => 'date',
      'params' => array(
        'date_type' => 'year_month_day',
        'date_format' => 'MM/DD/YYYY'
      )
    ));

    $custom_field_3 = CustomField::createOrUpdate(array(
      'name' => 'Registered on',
      'type' => 'date',
      'params' => array(
        'date_type' => 'year_month',
        'date_format' => 'MM/YYYY'
      )
    ));

    $subscriber_with_custom_field = Subscriber::createOrUpdate(array(
      'email' => 'user.with.cf@mailpoet.com',
      'cf_'.$custom_field->id => 'Paris',
      'cf_'.$custom_field_2->id => array(
        'day' => 9,
        'month' => 3,
        'year' => 1984
      ), // date as array value
      'cf_'.$custom_field_3->id => '2013-07' // date as string value
    ));

    $subscriber = Subscriber::findOne($subscriber_with_custom_field->id)
      ->withCustomFields();

    expect($subscriber->id)->equals($subscriber_with_custom_field->id);
    expect($subscriber->email)->equals('user.with.cf@mailpoet.com');
    expect($subscriber->{'cf_'.$custom_field->id})->equals('Paris');
    // date specified as array gets converted to string
    expect($subscriber->{'cf_'.$custom_field_2->id})->equals('1984-03-09 00:00:00');
    // date specified as string is stored as is
    expect($subscriber->{'cf_'.$custom_field_3->id})->equals('2013-07');
  }

  function testItShouldUnsubscribeFromAllSegments() {
    $segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $segment_1->id,
        $segment_2->id
      )
    ));

    $subscriber = Subscriber::findOne($subscriber->id);

    $subscribed_segments = $subscriber->segments()->findArray();
    expect($subscribed_segments)->count(2);
    expect($subscribed_segments[0]['name'] = 'Segment 1');
    expect($subscribed_segments[1]['name'] = 'Segment 2');

    // update subscriber status
    $unsubscribed_subscriber = Subscriber::createOrUpdate(array(
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED
    ));

    $subscribed_segments = $subscriber->segments()->findArray();
    expect($subscribed_segments)->count(0);
  }

  function testItCanCreateOrUpdate() {
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

  function testItCanCreateOrUpdateMultipleRecords() {
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

  function testItCanSubscribe() {
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
    // signup confirmation is enabled by default
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->deleted_at)->equals(null);
  }

  function testItCannotSubscribeWithReservedColumns() {
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();

    $subscriber = Subscriber::subscribe(
      array(
        'email' => 'donald@mailpoet.com',
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        // the fields below should NOT be taken into account
        'id' => 1337,
        'wp_user_id' => 7331,
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'created_at' => '1984-03-09 00:00:01',
        'updated_at' => '1984-03-09 00:00:02',
        'deleted_at' => '1984-03-09 00:00:03'
      ),
      array($segment->id())
    );

    expect($subscriber->id > 0)->equals(true);
    expect($subscriber->id)->notEquals(1337);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals('donald@mailpoet.com');
    expect($subscriber->first_name)->equals('Donald');
    expect($subscriber->last_name)->equals('Trump');

    expect($subscriber->wp_user_id)->equals(null);
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->created_at)->notEquals('1984-03-09 00:00:01');
    expect($subscriber->updated_at)->notEquals('1984-03-09 00:00:02');
    expect($subscriber->created_at)->equals($subscriber->updated_at);
    expect($subscriber->deleted_at)->equals(null);
  }

  function testItCanBeUpdatedByEmail() {
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

  function testItCanSetCustomField() {
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

  function testItCanGetCustomField() {
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

  function testItCanGetOnlySubscribedAndNonTrashedSubscribersInSegments() {
    $subscriber_1 = Subscriber::createOrUpdate(array(
      'first_name' => 'Adam',
      'last_name' => 'Smith',
      'email' => 'adam@smith.com',
      'status' => Subscriber::STATUS_UNCONFIRMED
    ));

    $subscriber_2 = Subscriber::createOrUpdate(array(
      'first_name' => 'Mary',
      'last_name' => 'Jane',
      'email' => 'mary@jane.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    ));

    $subscriber_3 = Subscriber::createOrUpdate(array(
      'first_name' => 'Bob',
      'last_name' => 'Smith',
      'email' => 'bob@smith.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'deleted_at' => Carbon::now()
    ));

    $segment = Segment::createOrUpdate(array(
      'name' => 'Only Subscribed Subscribers Segment'
    ));

    $result = SubscriberSegment::subscribeManyToSegments(
      array($subscriber_1->id, $subscriber_2->id, $subscriber_3->id),
      array($segment->id)
    );
    expect($result)->true();

    $subscribed_subscribers_in_segment = Subscriber::getSubscribedInSegments(
      array($segment->id)
    )->findArray();
    expect($subscribed_subscribers_in_segment)->count(1);

    // update 1st subscriber's state to subscribed
    $subscriber = Subscriber::findOne($subscriber_1->id);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $subscribed_subscribers_in_segment = Subscriber::getSubscribedInSegments(
      array($segment->id)
    )->findArray();
    expect($subscribed_subscribers_in_segment)->count(2);
  }

  function testItCannotTrashWpUser() {
    $wp_subscriber = Subscriber::createOrUpdate(array(
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1
    ));
    expect($wp_subscriber->trash())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
    expect($subscriber->deleted_at)->equals(null);
  }

  function testItCannotDeleteWpUser() {
    $wp_subscriber = Subscriber::createOrUpdate(array(
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1
    ));
    expect($wp_subscriber->delete())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
  }

  function testItCanDeleteCustomFieldRelations() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    foreach(range(1, 5) as $custom_field) {
      $subscriber_custom_field = SubscriberCustomField::create();
      $subscriber_custom_field->custom_field_id = $custom_field;
      $subscriber_custom_field->subscriber_id = ($custom_field !== 5) ?
        $subscriber->id :
        100; // create one record with a nonexistent subscriber id
      $subscriber_custom_field->save();
    }
    expect(SubscriberCustomField::findMany())->count(5);
    $subscriber->delete();
    expect(SubscriberCustomField::findMany())->count(1);
  }

  function testItCanGetTheTotalNumberOfSubscribers() {
    // remove all subscribers
    Subscriber::deleteMany();

    $subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'subscriber_1@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    ));

    $subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'subscriber_2@mailpoet.com',
      'status' => Subscriber::STATUS_UNCONFIRMED
    ));

    $subscriber_3 = Subscriber::createOrUpdate(array(
      'email' => 'subscriber_3@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED
    ));

    $subscriber_4 = Subscriber::createOrUpdate(array(
      'email' => 'subscriber_4@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'deleted_at' => Carbon::now()->toDateTimeString()
    ));

    $subscriber_5 = Subscriber::createOrUpdate(array(
      'email' => 'subscriber_5@mailpoet.com',
      'status' => Subscriber::STATUS_BOUNCED
    ));

    // counts only subscribed & unconfirmed users
    $total = Subscriber::getTotalSubscribers();
    expect($total)->equals(2);

    $subscriber_1->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber_1->save();

    $total = Subscriber::getTotalSubscribers();
    expect($total)->equals(1);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}
