<?php
namespace MailPoet\Test\Models;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;

class SubscriberTest extends \MailPoetTest {

  /** @var array */
  private $test_data;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->test_data = [
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ];
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate($this->test_data);
    $this->saved = $this->subscriber->save();
    $this->settings = new SettingsController();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  function testItCanBeCreated() {
    expect($this->saved->id() > 0)->true();
    expect($this->saved->getErrors())->false();
  }

  function testItHasFirstName() {
    $subscriber =
      Subscriber::where('email', $this->test_data['email'])
        ->findOne();
    expect($subscriber->first_name)
      ->equals($this->test_data['first_name']);
  }

  function testItHasLastName() {
    $subscriber =
      Subscriber::where('email', $this->test_data['email'])
        ->findOne();
    expect($subscriber->last_name)
      ->equals($this->test_data['last_name']);
  }

  function testItHasEmail() {
    $subscriber =
      Subscriber::where('email', $this->test_data['email'])
        ->findOne();
    expect($subscriber->email)
      ->equals($this->test_data['email']);
  }

  function testItShouldSetErrors() {
    // pdo error
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'email' => 'test@test.com',
      'invalid_column' => true,
    ]);
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
    $conflict_subscriber->hydrate($this->test_data);
    $saved = $conflict_subscriber->save();
    expect($saved)->notEquals(true);
  }

  function testItHasStatusDefaultStatusOfUnconfirmed() {
    $subscriber =
      Subscriber::where('email', $this->test_data['email'])
        ->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  function testItCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->test_data['email'])->findOne();
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    expect($subscriber->id() > 0)->true();
    expect($subscriber->getErrors())->false();
    $subscriber_updated = Subscriber::where('email', $this->test_data['email'])
      ->findOne();
    expect($subscriber_updated->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItUpdateLastSubscribedAtCorrectly() {
    $subscriber = Subscriber::where('email', $this->test_data['email'])->findOne();
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->last_subscribed_at = null;
    $subscriber->save();
    $subscriber_updated = Subscriber::where('email', $this->test_data['email'])
      ->findOne();
    expect($subscriber_updated->last_subscribed_at)->null();

    // Change to subscribed updates last_updated_at
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $subscriber_updated = Subscriber::where('email', $this->test_data['email'])
      ->findOne();
    $last_subscribed_at = new Carbon($subscriber_updated->last_updated_at);
    expect($last_subscribed_at)->lessThan((new Carbon())->addSeconds(2));
    expect($last_subscribed_at)->greaterThan((new Carbon())->subSeconds(2));

    // Change to other status keeps last_updated_at
    $last_subscribed_at = (new Carbon())->subHour();
    $subscriber->last_subscribed_at = $last_subscribed_at;
    $subscriber->save();
    $subscriber->status = Subscriber::STATUS_INACTIVE;
    $subscriber->save();
    $subscriber_updated = Subscriber::where('email', $this->test_data['email'])
      ->findOne();
    expect($subscriber_updated->last_subscribed_at)->equals($last_subscribed_at->toDateTimeString());
  }

  function testItHasSearchFilter() {
    $subscriber = Subscriber::filter('search', 'john')
      ->findOne();
    expect($subscriber->first_name)->equals($this->test_data['first_name']);
    $subscriber = Subscriber::filter('search', 'mailer')
      ->findOne();
    expect($subscriber->last_name)->equals($this->test_data['last_name']);
    $subscriber = Subscriber::filter('search', 'mailpoet')
      ->findOne();
    expect($subscriber->email)->equals($this->test_data['email']);
  }

  function testItHasGroupFilter() {
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNCONFIRMED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    }

    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_SUBSCRIBED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNSUBSCRIBED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_BOUNCED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_BOUNCED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      expect($subscriber->status)->equals(Subscriber::STATUS_BOUNCED);
    }
  }

  function testItProvidesSegmentFilter() {
    $segment = Segment::createOrUpdate([
      'name' => 'Test segment',
    ]);
    $segment_2 = Segment::createOrUpdate([
      'name' => 'Test segment 2',
    ]);

    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$segment->id, $segment_2->id]
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

    $segment = Segment::createOrUpdate([
      'name' => 'Test segment',
    ]);
    $segment_2 = Segment::createOrUpdate([
      'name' => 'Test segment 2',
    ]);

    // not yet subscribed
    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    expect($subscribers)->count(1);
    $subscribers = Subscriber::filter('filterBy', ['segment' => $segment->id])
      ->findMany();
    expect($subscribers)->count(0);

    // subscribed to a segment
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id, $segment_2->id]
    );

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    expect($subscribers)->count(0);
    $subscribers = Subscriber::filter('filterBy', ['segment' => $segment->id])
      ->findMany();
    expect($subscribers)->count(1);

    // unsubscribed
    SubscriberSegment::unsubscribeFromSegments(
      $subscriber,
      [$segment->id, $segment_2->id]
    );

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    expect($subscribers)->count(1);

    // subscribed to trashed segments
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id, $segment_2->id]
    );
    $segment->trash();
    $segment_2->trash();

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    expect($subscribers)->count(1);
  }

  function testItCanHaveSegment() {
    $segment = Segment::createOrUpdate([
      'name' => 'some name',
    ]);
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
    $custom_field = CustomField::createOrUpdate([
      'name' => 'DOB',
      'type' => 'date',
    ]);

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
    $custom_field = CustomField::createOrUpdate([
      'name' => 'City',
      'type' => 'text',
    ]);

    $custom_field_2 = CustomField::createOrUpdate([
      'name' => 'Birthday',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month_day',
        'date_format' => 'MM/DD/YYYY',
      ],
    ]);

    $custom_field_3 = CustomField::createOrUpdate([
      'name' => 'Registered on',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month',
        'date_format' => 'MM/YYYY',
      ],
    ]);

    $subscriber_with_custom_field = Subscriber::createOrUpdate([
      'email' => 'user.with.cf@mailpoet.com',
      'cf_' . $custom_field->id => 'Paris',
      'cf_' . $custom_field_2->id => [
        'day' => 9,
        'month' => 3,
        'year' => 1984,
      ], // date as array value
      'cf_' . $custom_field_3->id => '2013-07', // date as string value
    ]);

    $subscriber = Subscriber::findOne($subscriber_with_custom_field->id)
      ->withCustomFields();

    expect($subscriber->id)->equals($subscriber_with_custom_field->id);
    expect($subscriber->email)->equals('user.with.cf@mailpoet.com');
    expect($subscriber->{'cf_' . $custom_field->id})->equals('Paris');
    // date specified as array gets converted to string
    expect($subscriber->{'cf_' . $custom_field_2->id})->equals('1984-03-09 00:00:00');
    // date specified as string is stored as is
    expect($subscriber->{'cf_' . $custom_field_3->id})->equals('2013-07');
  }

  function testItShouldUnsubscribeFromAllSegments() {
    $segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment_2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $subscriber = Subscriber::createOrUpdate([
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $segment_1->id,
        $segment_2->id,
      ],
    ]);

    $subscriber = Subscriber::findOne($subscriber->id);

    $subscribed_segments = $subscriber->segments()->findArray();
    expect($subscribed_segments)->count(2);
    expect($subscribed_segments[0]['name'] = 'Segment 1');
    expect($subscribed_segments[1]['name'] = 'Segment 2');

    // update subscriber status
    $unsubscribed_subscriber = Subscriber::createOrUpdate([
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);

    $subscribed_segments = $subscriber->segments()->findArray();
    expect($subscribed_segments)->count(0);
  }

  function testItCanCreateOrUpdate() {
    $data = [
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ];
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
    \ORM::forTable(Subscriber::$_table)->deleteMany();
    $columns = [
      'first_name',
      'last_name',
      'email',
      'status',
    ];
    $values = [
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => 'unsubscribed',
      ],
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => 'unsubscribed',
      ],
    ];
    Subscriber::createMultiple($columns, $values);
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[1]['email'])->equals($values[1]['email']);
    expect($subscribers[1]['status'])->equals($values[1]['status']);

    $values[0]['first_name'] = 'John';
    $values[0]['status'] = 'subscribed';
    Subscriber::updateMultiple($columns, $values);
    $subscribers = Subscriber::findArray();
    expect($subscribers[0]['first_name'])->equals($values[0]['first_name']);
    expect($subscribers[0]['status'])->equals('unsubscribed');
  }

  function testItCanBeUpdatedByEmail() {
    $subscriber_updated = Subscriber::createOrUpdate([
      'email' => $this->test_data['email'],
      'first_name' => 'JoJo',
      'last_name' => 'DoDo',
    ]);

    expect($this->subscriber->id())->equals($subscriber_updated->id());

    $subscriber = Subscriber::findOne($this->subscriber->id());
    expect($subscriber->email)->equals($this->test_data['email']);
    expect($subscriber->first_name)->equals('JoJo');
    expect($subscriber->last_name)->equals('DoDo');
  }

  function testItCanSetCustomField() {
    $custom_field = CustomField::createOrUpdate([
      'name' => 'Date of Birth',
      'type' => 'date',
    ]);

    expect($custom_field->id() > 0)->true();

    $value = [
      'year' => 1984,
      'month' => 3,
      'day' => 9,
    ];

    $subscriber = Subscriber::findOne($this->subscriber->id());
    $subscriber->setCustomField($custom_field->id(), $value);

    $subscriber = $subscriber->withCustomFields()->asArray();

    expect($subscriber['cf_' . $custom_field->id()])->equals(
      mktime(0, 0, 0, $value['month'], $value['day'], $value['year'])
    );
  }

  function testItCanGetCustomField() {
    $subscriber = Subscriber::findOne($this->subscriber->id());

    expect($subscriber->getCustomField(9999, 'default_value'))
      ->equals('default_value');

    $custom_field = CustomField::createOrUpdate([
      'name' => 'Custom field: text input',
      'type' => 'input',
    ]);

    $subscriber->setCustomField($custom_field->id(), 'non_default_value');

    expect($subscriber->getCustomField($custom_field->id(), 'default_value'))
      ->equals('non_default_value');
  }

  function testItCanGetOnlySubscribedAndNonTrashedSubscribersInSegments() {
    $subscriber_1 = Subscriber::createOrUpdate([
      'first_name' => 'Adam',
      'last_name' => 'Smith',
      'email' => 'adam@smith.com',
      'status' => Subscriber::STATUS_UNCONFIRMED,
    ]);

    $subscriber_2 = Subscriber::createOrUpdate([
      'first_name' => 'Mary',
      'last_name' => 'Jane',
      'email' => 'mary@jane.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    $subscriber_3 = Subscriber::createOrUpdate([
      'first_name' => 'Bob',
      'last_name' => 'Smith',
      'email' => 'bob@smith.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'deleted_at' => Carbon::now(),
    ]);

    $segment = Segment::createOrUpdate([
      'name' => 'Only Subscribed Subscribers Segment',
    ]);

    $result = SubscriberSegment::subscribeManyToSegments(
      [$subscriber_1->id, $subscriber_2->id, $subscriber_3->id],
      [$segment->id]
    );
    expect($result)->true();

    $subscribed_subscribers_in_segment = Subscriber::getSubscribedInSegments(
      [$segment->id]
    )->findArray();
    expect($subscribed_subscribers_in_segment)->count(1);

    // update 1st subscriber's state to subscribed
    $subscriber = Subscriber::findOne($subscriber_1->id);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $subscribed_subscribers_in_segment = Subscriber::getSubscribedInSegments(
      [$segment->id]
    )->findArray();
    expect($subscribed_subscribers_in_segment)->count(2);
  }

  function testItCannotTrashWpUser() {
    $wp_subscriber = Subscriber::createOrUpdate([
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1,
    ]);
    expect($wp_subscriber->trash())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
    expect($subscriber->deleted_at)->equals(null);
  }

  function testItCannotDeleteWpUser() {
    $wp_subscriber = Subscriber::createOrUpdate([
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1,
    ]);
    expect($wp_subscriber->delete())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
  }

  function testItCannotTrashWooCommerceCustomer() {
    $wp_subscriber = Subscriber::createOrUpdate([
      'email' => 'some.woocommerce.customer@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WooCommerce Customer',
      'is_woocommerce_user' => 1,
    ]);
    expect($wp_subscriber->trash())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
    expect($subscriber->deleted_at)->equals(null);
  }

  function testItCannotDeleteWooCommerceCustomer() {
    $wp_subscriber = Subscriber::createOrUpdate([
      'email' => 'some.woocommerce.customer@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WooCommerce Customer',
      'is_woocommerce_user' => 1,
    ]);
    expect($wp_subscriber->delete())->equals(false);

    $subscriber = Subscriber::findOne($wp_subscriber->id);
    expect($subscriber)->notEquals(false);
  }

  function testItCanDeleteCustomFieldRelations() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    foreach (range(1, 5) as $custom_field) {
      $subscriber_custom_field = SubscriberCustomField::create();
      $subscriber_custom_field->custom_field_id = $custom_field;
      $subscriber_custom_field->subscriber_id = ($custom_field !== 5) ?
        $subscriber->id :
        100; // create one record with a nonexistent subscriber id
      $subscriber_custom_field->value = 'somevalue';
      $subscriber_custom_field->save();
    }
    expect(SubscriberCustomField::findMany())->count(5);
    $subscriber->delete();
    expect(SubscriberCustomField::findMany())->count(1);
  }

  function testItCanGetTheTotalNumberOfSubscribers() {
    // remove all subscribers
    Subscriber::deleteMany();

    $subscriber_1 = Subscriber::createOrUpdate([
      'email' => 'subscriber_1@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    $subscriber_2 = Subscriber::createOrUpdate([
      'email' => 'subscriber_2@mailpoet.com',
      'status' => Subscriber::STATUS_UNCONFIRMED,
    ]);

    $subscriber_3 = Subscriber::createOrUpdate([
      'email' => 'subscriber_3@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);

    $subscriber_4 = Subscriber::createOrUpdate([
      'email' => 'subscriber_4@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'deleted_at' => Carbon::now()->toDateTimeString(),
    ]);

    $subscriber_5 = Subscriber::createOrUpdate([
      'email' => 'subscriber_5@mailpoet.com',
      'status' => Subscriber::STATUS_BOUNCED,
    ]);

    // counts only subscribed & unconfirmed users
    $total = Subscriber::getTotalSubscribers();
    expect($total)->equals(2);

    $subscriber_1->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber_1->save();

    $total = Subscriber::getTotalSubscribers();
    expect($total)->equals(1);
  }

  function testItGeneratesSubscriberToken() {
    $token = Subscriber::generateToken($this->test_data['email']);
    expect(strlen($token))->equals(32);
  }

  function testItVerifiesSubscriberToken() {
    $token = Subscriber::generateToken($this->test_data['email']);
    expect(Subscriber::verifyToken($this->test_data['email'], $token))->true();
    expect(Subscriber::verifyToken('fake@email.com', $token))->false();
  }

  function testItVerifiesTokensOfDifferentLengths() {
    $token = Subscriber::generateToken($this->test_data['email'], 6);
    expect(Subscriber::verifyToken($this->test_data['email'], $token))->true();
  }

  function testItBulkDeletesSubscribers() {
    $segment = Segment::createOrUpdate(
      [
        'name' => 'test',
      ]
    );
    $custom_field = CustomField::createOrUpdate(
      [
        'name' => 'name',
        'type' => 'type',
        'params' => [
          'label' => 'label',
        ],
      ]
    );
    $subscriber_custom_field = SubscriberCustomField::createOrUpdate(
      [
        'subscriber_id' => $this->subscriber->id,
        'custom_field_id' => $custom_field->id,
        'value' => 'test',
      ]
    );
    expect(SubscriberCustomField::findMany())->count(1);
    $subscriber_segment = SubscriberSegment::createOrUpdate(
      [
        'subscriber_id' => $this->subscriber->id,
        'segment_id' => 1,
      ]
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
      for ($i = 1; $i <= 3; $i++) {
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
      return [
        $subscriber,
        $segment,
        $subscriber_segment,
      ];
    };

    // it should not find deleted and nonexistent subscribers
    list($subscriber, $segment,) = $prepare_data();
    $subscriber[1]->deleted_at = date("Y-m-d H:i:s");
    $subscriber[1]->save();
    $subscriber[2]->delete();
    $subscribers = Subscriber::findSubscribersInSegments(
      [
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id,
      ],
      [
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id,
      ]
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      [$subscriber[3]->id]
    );

    // it should not find subscribers with global unsubscribe status
    list($subscriber, $segment,) = $prepare_data();
    $subscriber[2]->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber[2]->save();
    $subscribers = Subscriber::findSubscribersInSegments(
      [
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id,
      ],
      [
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id,
      ]
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      [
        $subscriber[1]->id,
        $subscriber[3]->id,
      ]
    );

    // it should not find subscribers unsubscribed from segment or when segment doesn't exist
    list($subscriber, $segment, $subscriber_segment) = $prepare_data();
    $subscriber_segment[3]->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber_segment[3]->save();
    $subscriber_segment[2]->delete();
    $subscribers = Subscriber::findSubscribersInSegments(
      [
        $subscriber[1]->id,
        $subscriber[2]->id,
        $subscriber[3]->id,
      ],
      [
        $segment[1]->id,
        $segment[2]->id,
        $segment[3]->id,
      ]
    )->findMany();
    expect(Subscriber::extractSubscribersIds($subscribers))->equals(
      [$subscriber[1]->id]
    );
  }

  function testItSetsDefaultValuesForRequiredFields() {
    // MySQL running in strict mode requires a value to be set for certain fields
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    expect($values['first_name'])->equals('');
    expect($values['last_name'])->equals('');
    expect($values['status'])->equals(Subscriber::STATUS_UNCONFIRMED);
    expect(strlen($values['unsubscribe_token']))->equals(15);
  }

  function testItSetsDefaultStatusDependingOnSingupConfirmationOption() {
    // when signup confirmation is disabled, status should be 'subscribed'
    $this->settings->set('signup_confirmation.enabled', false);
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    expect($values['first_name'])->equals('');
    expect($values['last_name'])->equals('');
    expect($values['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    expect(strlen($values['unsubscribe_token']))->equals(15);

    $this->settings->set('signup_confirmation.enabled', true);
    // when signup confirmation is enabled, status should be 'unconfirmed'
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    expect($values['status'])->equals(Subscriber::STATUS_UNCONFIRMED);

    // when status is specified, it should not change regardless of signup confirmation option
    $this->settings->set('signup_confirmation.enabled', true);
    $values = Subscriber::setRequiredFieldsDefaultValues(['status' => Subscriber::STATUS_SUBSCRIBED]);
    expect($values['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  function testItSetsDefaultValuesForNewSubscribers() {
    $result = Subscriber::createOrUpdate(
      [
        'email' => 'new.subscriber@example.com',
      ]
    );
    expect($result->getErrors())->false();
    expect($result->first_name)->isEmpty();
    expect($result->last_name)->isEmpty();
    expect($result->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  function testItDoesNotSetDefaultValuesForExistingSubscribers() {
    $existing_subscriber_data = $this->test_data;
    $result = Subscriber::createOrUpdate(
      [
        'email' => $existing_subscriber_data['email'],
      ]
    );
    expect($result->getErrors())->false();
    expect($result->first_name)->equals($this->test_data['first_name']);
    expect($result->last_name)->equals($this->test_data['last_name']);
  }

  function testItExtractsCustomFieldsFromObject() {
    $data = [
      'email' => 'test@example.com',
      'cf_1' => 'Paris',
      'first_name' => 'John',
      'cf_2' => 'France',
      'last_name' => 'Doe',
    ];
    list($data, $custom_values) = Subscriber::extractCustomFieldsFromFromObject($data);
    expect($data)->equals(
      [
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
      ]
    );
    expect($custom_values)->equals(
      [
        '1' => 'Paris',
        '2' => 'France',
      ]
    );
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
