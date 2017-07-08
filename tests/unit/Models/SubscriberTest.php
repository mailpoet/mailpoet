<?php

use AspectMock\Test as Mock;
use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
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

  function testItValidatesEmailAndSetsErrors() {
    // email is required
    $subscriber = Subscriber::create();
    $subscriber->save();
    $errors = $subscriber->getErrors();
    expect($errors)->contains("Please enter your email address");

    // email address should be valid
    $subscriber = Subscriber::create();
    $subscriber->email = 'invalid_email';
    $subscriber->save();
    $errors = $subscriber->getErrors();
    expect($errors)->contains("Your email address is invalid!");

    $subscriber = Subscriber::create();
    $subscriber->email = 'tést@éxample.com';
    $subscriber->save();
    $errors = $subscriber->getErrors();
    expect($errors)->contains("Your email address is invalid!");
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

  function testItProvidesSegmentFilter() {
    $segment = Segment::createOrUpdate(array(
      'name' => 'Test segment'
    ));
    $segment_2 = Segment::createOrUpdate(array(
      'name' => 'Test segment 2'
    ));

    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      array($segment->id, $segment_2->id)
    );

    // all, none + segments
    $filters = Subscriber::filters();
    expect($filters['segment'])->count(4);

    // does not include trashed segments
    $segment->trash();
    $filters = Subscriber::filters();
    expect($filters['segment'])->count(3);
  }

  function testItAppliesSegmentFilter() {
    // remove all subscribers
    Subscriber::deleteMany();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();

    $segment = Segment::createOrUpdate(array(
      'name' => 'Test segment'
    ));
    $segment_2 = Segment::createOrUpdate(array(
      'name' => 'Test segment 2'
    ));

    // not yet subscribed
    $subscribers = Subscriber::filter('filterBy', array('segment' => 'none'))
      ->findMany();
    expect($subscribers)->count(1);
    $subscribers = Subscriber::filter('filterBy', array('segment' => $segment->id))
      ->findMany();
    expect($subscribers)->count(0);

    // subscribed to a segment
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      array($segment->id, $segment_2->id)
    );

    $subscribers = Subscriber::filter('filterBy', array('segment' => 'none'))
      ->findMany();
    expect($subscribers)->count(0);
    $subscribers = Subscriber::filter('filterBy', array('segment' => $segment->id))
      ->findMany();
    expect($subscribers)->count(1);

    // unsubscribed
    SubscriberSegment::unsubscribeFromSegments(
      $subscriber,
      array($segment->id, $segment_2->id)
    );

    $subscribers = Subscriber::filter('filterBy', array('segment' => 'none'))
      ->findMany();
    expect($subscribers)->count(1);
    $subscribers = Subscriber::filter('filterBy', array('segment' => $segment->id))
      ->findMany();
    expect($subscribers)->count(0);

    // subscribed to trashed segments
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      array($segment->id, $segment_2->id)
    );
    $segment->trash();
    $segment_2->trash();

    $subscribers = Subscriber::filter('filterBy', array('segment' => 'none'))
      ->findMany();
    expect($subscribers)->count(1);
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

  function testItSchedulesWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsDisabled() {
    // create segment
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();
    expect($segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $newsletter_options = array(
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1
    );
    foreach($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    $signup_confirmation_enabled = (bool)Setting::setValue(
      'signup_confirmation.enabled', false
    );
    $subscriber = Subscriber::subscribe($this->data, array($segment->id()));
    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    $scheduled_notification = SendingQueue::where('newsletter_id', $newsletter->id)
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduled_notification)->notEmpty();
  }

  function testItDoesNotScheduleWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsEnabled() {
    // create segment
    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();
    expect($segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $newsletter_options = array(
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1
    );
    foreach($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    $signup_confirmation_enabled = (bool)Setting::setValue(
      'signup_confirmation.enabled', true
    );
    $subscriber = Subscriber::subscribe($this->data, array($segment->id()));
    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    $scheduled_notification = SendingQueue::where('newsletter_id', $newsletter->id)
      ->where('status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduled_notification)->notEmpty();
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

  function testItOverwritesSubscriberDataWhenConfirmationIsDisabled() {
    $original_setting_value = Setting::getValue('signup_confirmation.enabled');
    Setting::setValue('signup_confirmation.enabled', false);

    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(array('name' => 'List #2'));
    $segment2->save();

    $data = array(
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    );

    $subscriber = Subscriber::subscribe(
      $data,
      array($segment->id())
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->first_name)->equals($data['first_name']);
    expect($subscriber->last_name)->equals($data['last_name']);

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    $subscriber = Subscriber::subscribe(
      $data2,
      array($segment->id(), $segment2->id())
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    expect($subscriber->email)->equals($data2['email']);
    expect($subscriber->first_name)->equals($data2['first_name']);
    expect($subscriber->last_name)->equals($data2['last_name']);

    Setting::setValue('signup_confirmation.enabled', $original_setting_value);
  }

  function testItStoresUnconfirmedSubscriberDataWhenConfirmationIsEnabled() {
    $original_setting_value = Setting::getValue('signup_confirmation.enabled');
    Setting::setValue('signup_confirmation.enabled', true);

    $segment = Segment::create();
    $segment->hydrate(array('name' => 'List #1'));
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(array('name' => 'List #2'));
    $segment2->save();

    $data = array(
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    );

    $subscriber = Subscriber::subscribe(
      $data,
      array($segment->id())
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->first_name)->equals($data['first_name']);
    expect($subscriber->last_name)->equals($data['last_name']);

    expect($subscriber->unconfirmed_data)->isEmpty();

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    $subscriber = Subscriber::subscribe(
      $data2,
      array($segment->id(), $segment2->id())
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    // fields should be left intact
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->first_name)->equals($data['first_name']);
    expect($subscriber->last_name)->equals($data['last_name']);

    expect($subscriber->unconfirmed_data)->notEmpty();
    expect($subscriber->unconfirmed_data)->equals(json_encode($data2));

    // Unconfirmed data should be wiped after any direct update
    // during confirmation, manual admin editing
    $subscriber = Subscriber::createOrUpdate($data2);
    expect($subscriber->unconfirmed_data)->isEmpty();
    // during import
    $subscriber->unconfirmed_data = json_encode($data2);
    $subscriber->save();
    expect($subscriber->isDirty('unconfirmed_data'))->false();
    expect($subscriber->unconfirmed_data)->notEmpty();
    Subscriber::updateMultiple(
      array_keys($data2),
      array(array_values($data2))
    );
    $subscriber = Subscriber::where('email', $data2['email'])->findOne();
    expect($subscriber->unconfirmed_data)->isEmpty();

    Setting::setValue('signup_confirmation.enabled', $original_setting_value);
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

  function testItGeneratesSubscriberToken() {
    $token = Subscriber::generateToken($this->data['email']);
    expect(strlen($token))->equals(Subscriber::SUBSCRIBER_TOKEN_LENGTH);
  }

  function testItVerifiesSubscriberToken() {
    $token = Subscriber::generateToken($this->data['email']);
    expect(Subscriber::verifyToken($this->data['email'], $token))->true();
    expect(Subscriber::verifyToken('fake@email.com', $token))->false();
  }

  function testItVerifiesTokensOfDifferentLengths() {
    $token = md5(AUTH_KEY . $this->data['email']);
    expect(strlen($token))->notEquals(Subscriber::SUBSCRIBER_TOKEN_LENGTH);
    expect(Subscriber::verifyToken($this->data['email'], $token))->true();
  }

  function testItBulkDeletesSubscribers() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'test'
      )
    );
    $custom_field = CustomField::createOrUpdate(
      array(
        'name' => 'name',
        'type' => 'type',
        'params' => array(
          'label' => 'label'
        ),
      )
    );
    $subscriber_custom_field = SubscriberCustomField::createOrUpdate(
      array(
        'subscriber_id' => $this->subscriber->id,
        'custom_field_id' => $custom_field->id,
        'value' => 'test',
      )
    );
    expect(SubscriberCustomField::findMany())->count(1);
    $subscriber_segment = SubscriberSegment::createOrUpdate(
      array(
        'subscriber_id' => $this->subscriber->id,
        'segment_id' => 1
      )
    );
    expect(SubscriberSegment::findMany())->count(1);

    // associated segments and custom fields should be deleted
    Subscriber::filter('bulkDelete');
    expect(SubscriberCustomField::findArray())->isEmpty();
    expect(SubscriberSegment::findArray())->isEmpty();
    expect(Subscriber::findArray())->isEmpty();
  }

  function testItCanFindSubscribersInSegments() {
    // create 3 subscribers, segments and subscriber-segment relations
    $prepare_data = function() {
      $this->_after();
      for($i = 1; $i <= 3; $i++) {
        $subscriber[$i] = Subscriber::create();
        $subscriber[$i]->status = Subscriber::STATUS_SUBSCRIBED;
        $subscriber[$i]->email = $i . '@test.com';
        $subscriber[$i]->first_name = 'first ' . $i;
        $subscriber[$i]->last_name = 'last ' . $i;
        $subscriber[$i]->save();
        $segment[$i] = Segment::create();
        $segment[$i]->name = 'segment ' . $i;
        $segment[$i]->save();
        $subscriber_segment[$i] = SubscriberSegment::create();
        $subscriber_segment[$i]->subscriber_id = $subscriber[$i]->id;
        $subscriber_segment[$i]->segment_id = $segment[$i]->id;
        $subscriber_segment[$i]->save();
      }
      return array(
        $subscriber,
        $segment,
        $subscriber_segment
      );
    };

    // it should not find deleted and nonexistent subscribers
    list($subscriber, $segment,) = $prepare_data();
    $subscriber[1]->deleted_at = date("Y-m-d H:i:s");
    $subscriber[1]->save();
    $subscriber[2]->delete();
    $subscribers = Subscriber::findSubscribersInSegments(
      array(
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id
      ),
      array(
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id
      )
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      array($subscriber[3]->id)
    );

    // it should not find subscribers with global unsubscribe status
    list($subscriber, $segment,) = $prepare_data();
    $subscriber[2]->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber[2]->save();
    $subscribers = Subscriber::findSubscribersInSegments(
      array(
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id
      ),
      array(
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id
      )
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      array(
        $subscriber[1]->id,
        $subscriber[3]->id
      )
    );

    // it should not find subscribers unsubscribed from segment or when segment doesn't exist
    list($subscriber, $segment, $subscriber_segment) = $prepare_data();
    $subscriber_segment[3]->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber_segment[3]->save();
    $subscriber_segment[2]->delete();
    $subscribers = Subscriber::findSubscribersInSegments(
      array(
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id
      ),
      array(
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id
      )
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      array($subscriber[1]->id)
    );
  }

  function testItSetsDefaultValuesForRequiredFields() {
    // MySQL running in strict mode requires a value to be set for certain fields
    expect(Subscriber::setRequiredFieldsDefaultValues(array()))->equals(
      array(
        'first_name' => '',
        'last_name' => ''
      )
    );
  }

  function testItExtractsCustomFieldsFromObject() {
    $data = array(
      'email' => 'test@example.com',
      'cf_1' => 'Paris',
      'first_name' => 'John',
      'cf_2' => 'France',
      'last_name' => 'Doe'
    );
    list($data, $custom_values) = Subscriber::extractCustomFieldsFromFromObject($data);
    expect($data)->equals(
      array(
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe'
      )
    );
    expect($custom_values)->equals(
      array(
        '1' => 'Paris',
        '2' => 'France'
      )
    );
  }

  function testItSendsConfirmationEmail() {
    Mock::double('MailPoet\Mailer\Mailer', [
      '__construct' => null,
      'send' => function($email) {
        return $email;
      }
    ]);
    Mock::double('MailPoet\Subscription\Url', [
      'getConfirmationUrl' => 'http://example.com'
    ]);

    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Test segment'
      )
    );
    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      array($segment->id)
    );

    $result = $this->subscriber->sendConfirmationEmail();

    // email contains subscriber's lists
    expect($result['body']['html'])->contains('<strong>Test segment</strong>');
    // email contains activation link
    expect($result['body']['html'])->contains('<a target="_blank" href="http://example.com">Click here to confirm your subscription.</a>');
  }

  function testItSetsErrorsWhenConfirmationEmailCannotBeSent() {
    Mock::double('MailPoet\Mailer\Mailer', [
      '__construct' => null,
      'send' => function() {
        throw new \Exception('send error');
      }
    ]);

    $this->subscriber->sendConfirmationEmail();
    // error is set on the subscriber model object
    expect($this->subscriber->getErrors()[0])->equals('send error');
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}