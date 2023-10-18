<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use Codeception\Util\Fixtures;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class SubscriberTest extends \MailPoetTest {
  public $saved;
  public $subscriber;

  /** @var array */
  private $testData;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->testData = [
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ];
    $this->subscriber = Subscriber::create();
    $this->subscriber->hydrate($this->testData);
    $this->saved = $this->subscriber->save();
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCanBeCreated() {
    verify($this->saved->id() > 0)->true();
    verify($this->saved->getErrors())->false();
  }

  public function testItHasFirstName() {
    $subscriber =
      Subscriber::where('email', $this->testData['email'])
        ->findOne();
    verify($subscriber->firstName)
      ->equals($this->testData['first_name']);
  }

  public function testItHasLastName() {
    $subscriber =
      Subscriber::where('email', $this->testData['email'])
        ->findOne();
    verify($subscriber->lastName)
      ->equals($this->testData['last_name']);
  }

  public function testItHasEmail() {
    $subscriber =
      Subscriber::where('email', $this->testData['email'])
        ->findOne();
    verify($subscriber->email)
      ->equals($this->testData['email']);
  }

  public function testItShouldSetErrors() {
    // pdo error
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'email' => 'test@test.com',
      'invalid_column' => true,
    ]);
    $subscriber->save();
    $errors = $subscriber->getErrors();
    verify($errors[0])->stringContainsString("Unknown column 'invalid_column' in 'field list'");
  }

  public function testItValidatesEmailAndSetsErrors() {
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

  public function emailMustBeUnique() {
    $conflictSubscriber = Subscriber::create();
    $conflictSubscriber->hydrate($this->testData);
    $saved = $conflictSubscriber->save();
    verify($saved)->notEquals(true);
  }

  public function testItHasStatusDefaultStatusOfUnconfirmed() {
    $subscriber =
      Subscriber::where('email', $this->testData['email'])
        ->findOne();
    verify($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItCanChangeStatus() {
    $subscriber = Subscriber::where('email', $this->testData['email'])->findOne();
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    verify($subscriber->id() > 0)->true();
    verify($subscriber->getErrors())->false();
    $subscriberUpdated = Subscriber::where('email', $this->testData['email'])
      ->findOne();
    verify($subscriberUpdated->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItUpdateLastSubscribedAtCorrectly() {
    $subscriber = Subscriber::where('email', $this->testData['email'])->findOne();
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->lastSubscribedAt = null;
    $subscriber->save();
    $subscriberUpdated = Subscriber::where('email', $this->testData['email'])
      ->findOne();
    verify($subscriberUpdated->lastSubscribedAt)->null();

    // Change to subscribed updates last_updated_at
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $subscriberUpdated = Subscriber::where('email', $this->testData['email'])
      ->findOne();
    $lastSubscribedAt = new Carbon($subscriberUpdated->lastUpdatedAt);
    expect($lastSubscribedAt)->lessThan((new Carbon())->addSeconds(2));
    verify($lastSubscribedAt)->greaterThan((new Carbon())->subSeconds(2));

    // Change to other status keeps last_updated_at
    $lastSubscribedAt = (new Carbon())->subHour();
    $subscriber->lastSubscribedAt = $lastSubscribedAt;
    $subscriber->save();
    $subscriber->status = Subscriber::STATUS_INACTIVE;
    $subscriber->save();
    $subscriberUpdated = Subscriber::where('email', $this->testData['email'])
      ->findOne();
    verify($subscriberUpdated->lastSubscribedAt)->equals($lastSubscribedAt->toDateTimeString());
  }

  public function testItHasSearchFilter() {
    $subscriber = Subscriber::filter('search', 'john')
      ->findOne();
    verify($subscriber->firstName)->equals($this->testData['first_name']);
    $subscriber = Subscriber::filter('search', 'mailer')
      ->findOne();
    verify($subscriber->lastName)->equals($this->testData['last_name']);
    $subscriber = Subscriber::filter('search', 'mailpoet')
      ->findOne();
    verify($subscriber->email)->equals($this->testData['email']);
  }

  public function testItHasGroupFilter() {
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNCONFIRMED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      verify($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    }

    $this->subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_SUBSCRIBED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      verify($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_UNSUBSCRIBED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      verify($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    }

    $this->subscriber->status = Subscriber::STATUS_BOUNCED;
    $this->subscriber->save();
    $subscribers = Subscriber::filter('groupBy', Subscriber::STATUS_BOUNCED)
      ->findMany();
    foreach ($subscribers as $subscriber) {
      verify($subscriber->status)->equals(Subscriber::STATUS_BOUNCED);
    }
  }

  public function testItProvidesSegmentFilter() {
    $segment = Segment::createOrUpdate([
      'name' => 'Test segment',
    ]);
    $segment2 = Segment::createOrUpdate([
      'name' => 'Test segment 2',
    ]);

    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$segment->id, $segment2->id]
    );

    // all, none + segments
    $filters = Subscriber::filters();
    verify($filters['segment'])->arrayCount(4);

    // does not include trashed segments
    $segment->trash();
    $filters = Subscriber::filters();
    verify($filters['segment'])->arrayCount(3);
  }

  public function testItAppliesSegmentFilter() {
    // remove all subscribers
    Subscriber::deleteMany();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();

    $segment = Segment::createOrUpdate([
      'name' => 'Test segment',
    ]);
    $segment2 = Segment::createOrUpdate([
      'name' => 'Test segment 2',
    ]);

    // not yet subscribed
    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    verify($subscribers)->arrayCount(1);
    $subscribers = Subscriber::filter('filterBy', ['segment' => $segment->id])
      ->findMany();
    verify($subscribers)->arrayCount(0);

    // subscribed to a segment
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id, $segment2->id]
    );

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    verify($subscribers)->arrayCount(0);
    $subscribers = Subscriber::filter('filterBy', ['segment' => $segment->id])
      ->findMany();
    verify($subscribers)->arrayCount(1);

    // unsubscribed
    SubscriberSegment::unsubscribeFromSegments(
      $subscriber,
      [$segment->id, $segment2->id]
    );

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    verify($subscribers)->arrayCount(1);

    // subscribed to trashed segments
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$segment->id, $segment2->id]
    );
    $segment->trash();
    $segment2->trash();

    $subscribers = Subscriber::filter('filterBy', ['segment' => 'none'])
      ->findMany();
    verify($subscribers)->arrayCount(1);
  }

  public function testItCanHaveSegment() {
    $segment = Segment::createOrUpdate([
      'name' => 'some name',
    ]);
    verify($segment->getErrors())->false();

    $association = SubscriberSegment::create();
    $association->subscriberId = $this->subscriber->id;
    $association->segmentId = $segment->id;
    $association->save();

    $subscriber = Subscriber::findOne($this->subscriber->id);

    $subscriberSegment = $subscriber->segments()->findOne();
    verify($subscriberSegment->id)->equals($segment->id);
  }

  public function testItCanHaveCustomFields() {
    $customField = CustomField::createOrUpdate([
      'name' => 'DOB',
      'type' => 'date',
    ]);

    $association = SubscriberCustomField::create();
    $association->subscriberId = $this->subscriber->id;
    $association->customFieldId = $customField->id;
    $association->value = '12/12/2012';
    $association->save();

    $subscriber = Subscriber::filter('filterWithCustomFields')
      ->findOne($this->subscriber->id);
    verify($subscriber->DOB)->equals($association->value);
  }

  public function testItCanCreateSubscriberWithCustomFields() {
    $customField = CustomField::createOrUpdate([
      'name' => 'City',
      'type' => 'text',
    ]);

    $customField2 = CustomField::createOrUpdate([
      'name' => 'Birthday',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month_day',
        'date_format' => 'MM/DD/YYYY',
      ],
    ]);

    $customField4 = CustomField::createOrUpdate([
      'name' => 'Date in different format',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month_day',
        'date_format' => 'DD/MM/YYYY',
      ],
    ]);

    $customField3 = CustomField::createOrUpdate([
      'name' => 'Registered on',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month',
        'date_format' => 'MM/YYYY',
      ],
    ]);

    $customField5 = CustomField::createOrUpdate([
      'name' => 'Year-month in different format',
      'type' => 'date',
      'params' => [
        'date_type' => 'year_month',
        'date_format' => 'YYYY/MM',
      ],
    ]);

    $subscriberWithCustomField = Subscriber::createOrUpdate([
      'email' => 'user.with.cf@mailpoet.com',
      'cf_' . $customField->id => 'Paris',
      'cf_' . $customField2->id => [
        'day' => 9,
        'month' => 3,
        'year' => 1984,
      ], // date as array value
      'cf_' . $customField4->id => [
        'day' => 25,
        'month' => 4,
        'year' => 2020,
      ], // date as array value
      'cf_' . $customField3->id => '2013-07', // date as string value
      'cf_' . $customField5->id => [
        'month' => 5,
        'year' => 2020,
      ], // date as array value
    ]);

    $subscriber = Subscriber::findOne($subscriberWithCustomField->id)
      ->withCustomFields();

    verify($subscriber->id)->equals($subscriberWithCustomField->id);
    verify($subscriber->email)->equals('user.with.cf@mailpoet.com');
    verify($subscriber->{'cf_' . $customField->id})->equals('Paris');
    // date specified as array gets converted to string
    verify($subscriber->{'cf_' . $customField2->id})->equals('1984-03-09 00:00:00');
    // date in different format specified as array is stored correctly
    verify($subscriber->{'cf_' . $customField4->id})->equals('2020-04-25 00:00:00');
    // date specified as string is stored as is
    verify($subscriber->{'cf_' . $customField3->id})->equals('2013-07');
    // year-month date in different format specified as array is stored correctly
    verify($subscriber->{'cf_' . $customField5->id})->equals('2020-05-01 00:00:00');
  }

  public function testItShouldUnsubscribeFromAllSegments() {
    $segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $subscriber = Subscriber::createOrUpdate([
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $segment1->id,
        $segment2->id,
      ],
    ]);

    $subscriber = Subscriber::findOne($subscriber->id);

    $subscribedSegments = $subscriber->segments()->findArray();
    verify($subscribedSegments)->arrayCount(2);
    expect($subscribedSegments[0]['name'] = 'Segment 1');
    expect($subscribedSegments[1]['name'] = 'Segment 2');

    // update subscriber status
    $unsubscribedSubscriber = Subscriber::createOrUpdate([
      'email' => 'jean.louis@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);

    $subscribedSegments = $subscriber->segments()->findArray();
    verify($subscribedSegments)->arrayCount(0);
  }

  public function testItCanCreateOrUpdate() {
    $data = [
      'email' => 'john.doe@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ];
    $result = Subscriber::createOrUpdate($data);
    verify($result->id() > 0)->true();
    verify($result->getErrors())->false();

    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    verify($record->firstName)->equals($data['first_name']);
    verify($record->lastName)->equals($data['last_name']);
    $record->lastName = 'Mailer';
    $result = Subscriber::createOrUpdate($record->asArray());
    verify($result)->notEquals(false);
    verify($result->getValidationErrors())->empty();
    $record = Subscriber::where('email', $data['email'])
      ->findOne();
    verify($record->lastName)->equals('Mailer');
  }

  public function testItCanCreateOrUpdateMultipleRecords() {
    Subscriber::deleteMany();
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
    verify(count($subscribers))->equals(2);
    verify($subscribers[1]['email'])->equals($values[1]['email']);
    verify($subscribers[1]['status'])->equals($values[1]['status']);

    $values[0]['first_name'] = 'John';
    Subscriber::updateMultiple($columns, $values);
    $subscribers = Subscriber::findArray();
    verify($subscribers[0]['first_name'])->equals($values[0]['first_name']);
    verify($subscribers[0]['status'])->equals('unsubscribed');
  }

  public function testItCanBeUpdatedByEmail() {
    $subscriberUpdated = Subscriber::createOrUpdate([
      'email' => $this->testData['email'],
      'first_name' => 'JoJo',
      'last_name' => 'DoDo',
    ]);

    verify($this->subscriber->id())->equals($subscriberUpdated->id());

    $subscriber = Subscriber::findOne($this->subscriber->id());
    verify($subscriber->email)->equals($this->testData['email']);
    verify($subscriber->firstName)->equals('JoJo');
    verify($subscriber->lastName)->equals('DoDo');
  }

  public function testItCanSetCustomField() {
    $customField = CustomField::createOrUpdate([
      'name' => 'Date of Birth',
      'type' => 'date',
    ]);

    verify($customField->id() > 0)->true();

    $value = [
      'year' => 1984,
      'month' => 3,
      'day' => 9,
    ];

    $subscriber = Subscriber::findOne($this->subscriber->id());
    $subscriber->setCustomField($customField->id(), $value);

    $subscriber = $subscriber->withCustomFields()->asArray();

    verify($subscriber['cf_' . $customField->id()])->equals(
      mktime(0, 0, 0, $value['month'], $value['day'], $value['year'])
    );
  }

  public function testItCanGetCustomField() {
    $subscriber = Subscriber::findOne($this->subscriber->id());

    verify($subscriber->getCustomField(9999, 'default_value'))
      ->equals('default_value');

    $customField = CustomField::createOrUpdate([
      'name' => 'Custom field: text input',
      'type' => 'input',
    ]);

    $subscriber->setCustomField($customField->id(), 'non_default_value');

    verify($subscriber->getCustomField($customField->id(), 'default_value'))
      ->equals('non_default_value');
  }

  public function testItCanGetOnlySubscribedAndNonTrashedSubscribersInSegments() {
    $subscriber1 = Subscriber::createOrUpdate([
      'first_name' => 'Adam',
      'last_name' => 'Smith',
      'email' => 'adam@smith.com',
      'status' => Subscriber::STATUS_UNCONFIRMED,
    ]);

    $subscriber2 = Subscriber::createOrUpdate([
      'first_name' => 'Mary',
      'last_name' => 'Jane',
      'email' => 'mary@jane.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    $subscriber3 = Subscriber::createOrUpdate([
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
      [$subscriber1->id, $subscriber2->id, $subscriber3->id],
      [$segment->id]
    );
    verify($result)->true();

    $subscribedSubscribersInSegment = Subscriber::getSubscribedInSegments(
      [$segment->id]
    )->findArray();
    verify($subscribedSubscribersInSegment)->arrayCount(1);

    // update 1st subscriber's state to subscribed
    $subscriber = Subscriber::findOne($subscriber1->id);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    $subscribedSubscribersInSegment = Subscriber::getSubscribedInSegments(
      [$segment->id]
    )->findArray();
    verify($subscribedSubscribersInSegment)->arrayCount(2);
  }

  public function testItCannotTrashWpUser() {
    $wpSubscriber = Subscriber::createOrUpdate([
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1,
    ]);
    verify($wpSubscriber->trash())->equals(false);

    $subscriber = Subscriber::findOne($wpSubscriber->id);
    verify($subscriber)->notEquals(false);
    verify($subscriber->deletedAt)->equals(null);
  }

  public function testItCannotDeleteWpUser() {
    $wpSubscriber = Subscriber::createOrUpdate([
      'email' => 'some.wp.user@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WP User',
      'wp_user_id' => 1,
    ]);
    verify($wpSubscriber->delete())->equals(false);

    $subscriber = Subscriber::findOne($wpSubscriber->id);
    verify($subscriber)->notEquals(false);
  }

  public function testItCannotTrashWooCommerceCustomer() {
    $wpSubscriber = Subscriber::createOrUpdate([
      'email' => 'some.woocommerce.customer@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WooCommerce Customer',
      'is_woocommerce_user' => 1,
    ]);
    verify($wpSubscriber->trash())->equals(false);

    $subscriber = Subscriber::findOne($wpSubscriber->id);
    verify($subscriber)->notEquals(false);
    verify($subscriber->deletedAt)->equals(null);
  }

  public function testItCannotDeleteWooCommerceCustomer() {
    $wpSubscriber = Subscriber::createOrUpdate([
      'email' => 'some.woocommerce.customer@mailpoet.com',
      'first_name' => 'Some',
      'last_name' => 'WooCommerce Customer',
      'is_woocommerce_user' => 1,
    ]);
    verify($wpSubscriber->delete())->equals(false);

    $subscriber = Subscriber::findOne($wpSubscriber->id);
    verify($subscriber)->notEquals(false);
  }

  public function testItCanDeleteCustomFieldRelations() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    foreach (range(1, 5) as $customField) {
      $subscriberCustomField = SubscriberCustomField::create();
      $subscriberCustomField->customFieldId = $customField;
      $subscriberCustomField->subscriberId = ($customField !== 5) ?
        $subscriber->id :
        100; // create one record with a nonexistent subscriber id
      $subscriberCustomField->value = 'somevalue';
      $subscriberCustomField->save();
    }
    verify(SubscriberCustomField::findMany())->arrayCount(5);
    $subscriber->delete();
    verify(SubscriberCustomField::findMany())->arrayCount(1);
  }

  public function testItCanGetTheTotalNumberOfSubscribers() {
    // remove all subscribers
    Subscriber::deleteMany();

    $subscriber1 = Subscriber::createOrUpdate([
      'email' => 'subscriber_1@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

    $subscriber2 = Subscriber::createOrUpdate([
      'email' => 'subscriber_2@mailpoet.com',
      'status' => Subscriber::STATUS_UNCONFIRMED,
    ]);

    $subscriber3 = Subscriber::createOrUpdate([
      'email' => 'subscriber_3@mailpoet.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);

    $subscriber4 = Subscriber::createOrUpdate([
      'email' => 'subscriber_4@mailpoet.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'deleted_at' => Carbon::now()->toDateTimeString(),
    ]);

    $subscriber5 = Subscriber::createOrUpdate([
      'email' => 'subscriber_5@mailpoet.com',
      'status' => Subscriber::STATUS_BOUNCED,
    ]);

    // counts only subscribed & unconfirmed users
    $total = Subscriber::getTotalSubscribers();
    verify($total)->equals(2);

    $subscriber1->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber1->save();

    $total = Subscriber::getTotalSubscribers();
    verify($total)->equals(1);
  }

  public function testItDoesNotFindDeletedOrNonexistentSubscribersInSegments() {
    $subscriber = [];
    $segment = [];
    $subscriberSegment = [];
    for ($i = 1; $i <= 3; $i++) {
      $subscriber[$i] = Subscriber::create();
      $subscriber[$i]->status = Subscriber::STATUS_SUBSCRIBED;
      $subscriber[$i]->email = $i . '@test.com';
      $subscriber[$i]->firstName = 'first ' . $i;
      $subscriber[$i]->lastName = 'last ' . $i;
      $subscriber[$i]->save();
      $segment[$i] = Segment::create();
      $segment[$i]->name = 'segment ' . $i;
      $segment[$i]->save();
      $subscriberSegment[$i] = SubscriberSegment::create();
      $subscriberSegment[$i]->subscriberId = $subscriber[$i]->id;
      $subscriberSegment[$i]->segmentId = (int)$segment[$i]->id;
      $subscriberSegment[$i]->save();
    }
    $subscriber[1]->deletedAt = date("Y-m-d H:i:s");
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
    verify(Subscriber::extractSubscribersIds($subscribers))->equals(
      [$subscriber[3]->id]
    );
  }

  public function testItDoesNotFindGloballyUnsubscribedSubscribersInSegments() {
    $subscriber = [];
    $segment = [];
    $subscriberSegment = [];
    for ($i = 1; $i <= 3; $i++) {
      $subscriber[$i] = Subscriber::create();
      $subscriber[$i]->status = Subscriber::STATUS_SUBSCRIBED;
      $subscriber[$i]->email = $i . '@test.com';
      $subscriber[$i]->firstName = 'first ' . $i;
      $subscriber[$i]->lastName = 'last ' . $i;
      $subscriber[$i]->save();
      $segment[$i] = Segment::create();
      $segment[$i]->name = 'segment ' . $i;
      $segment[$i]->save();
      $subscriberSegment[$i] = SubscriberSegment::create();
      $subscriberSegment[$i]->subscriberId = $subscriber[$i]->id;
      $subscriberSegment[$i]->segmentId = (int)$segment[$i]->id;
      $subscriberSegment[$i]->save();
    }
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
    verify(Subscriber::extractSubscribersIds($subscribers))->equals(
      [
        $subscriber[1]->id,
        $subscriber[3]->id,
      ]
    );
  }

  public function testItShouldNotFindSubscribersUnsubscribedFromSegmentOrWhenSegmentDoesNotExist() {
    $subscriber = [];
    $segment = [];
    $subscriberSegment = [];
    for ($i = 1; $i <= 3; $i++) {
      $subscriber[$i] = Subscriber::create();
      $subscriber[$i]->status = Subscriber::STATUS_SUBSCRIBED;
      $subscriber[$i]->email = $i . '@test.com';
      $subscriber[$i]->firstName = 'first ' . $i;
      $subscriber[$i]->lastName = 'last ' . $i;
      $subscriber[$i]->save();
      $segment[$i] = Segment::create();
      $segment[$i]->name = 'segment ' . $i;
      $segment[$i]->save();
      $subscriberSegment[$i] = SubscriberSegment::create();
      $subscriberSegment[$i]->subscriberId = $subscriber[$i]->id;
      $subscriberSegment[$i]->segmentId = (int)$segment[$i]->id;
      $subscriberSegment[$i]->save();
    }
    $subscriberSegment[3]->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriberSegment[3]->save();
    $subscriberSegment[2]->delete();
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
    verify(Subscriber::extractSubscribersIds($subscribers))->equals(
      [$subscriber[1]->id]
    );
  }

  public function testItSetsDefaultValuesForRequiredFields() {
    // MySQL running in strict mode requires a value to be set for certain fields
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    verify($values['first_name'])->equals('');
    verify($values['last_name'])->equals('');
    verify($values['status'])->equals(Subscriber::STATUS_UNCONFIRMED);
    verify(strlen($values['unsubscribe_token']))->equals(15);
    verify(strlen($values['link_token']))->equals(32);
  }

  public function testItSetsDefaultStatusDependingOnSingupConfirmationOption() {
    // when signup confirmation is disabled, status should be 'subscribed'
    $this->settings->set('signup_confirmation.enabled', false);
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    verify($values['first_name'])->equals('');
    verify($values['last_name'])->equals('');
    verify($values['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    verify(strlen($values['unsubscribe_token']))->equals(15);

    $this->settings->set('signup_confirmation.enabled', true);
    // when signup confirmation is enabled, status should be 'unconfirmed'
    $values = Subscriber::setRequiredFieldsDefaultValues([]);
    verify($values['status'])->equals(Subscriber::STATUS_UNCONFIRMED);

    // when status is specified, it should not change regardless of signup confirmation option
    $this->settings->set('signup_confirmation.enabled', true);
    $values = Subscriber::setRequiredFieldsDefaultValues(['status' => Subscriber::STATUS_SUBSCRIBED]);
    verify($values['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItSetsDefaultValuesForNewSubscribers() {
    $result = Subscriber::createOrUpdate(
      [
        'email' => 'new.subscriber@example.com',
      ]
    );
    verify($result->getErrors())->false();
    verify($result->firstName)->empty();
    verify($result->lastName)->empty();
    verify($result->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItDoesNotSetDefaultValuesForExistingSubscribers() {
    $existingSubscriberData = $this->testData;
    $result = Subscriber::createOrUpdate(
      [
        'email' => $existingSubscriberData['email'],
      ]
    );
    verify($result->getErrors())->false();
    verify($result->firstName)->equals($this->testData['first_name']);
    verify($result->lastName)->equals($this->testData['last_name']);
  }

  public function testItExtractsCustomFieldsFromObject() {
    $data = [
      'email' => 'test@example.com',
      'cf_1' => 'Paris',
      'first_name' => 'John',
      'cf_2' => 'France',
      'last_name' => 'Doe',
    ];
    list($data, $customValues) = Subscriber::extractCustomFieldsFromFromObject($data);
    verify($data)->equals(
      [
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
      ]
    );
    verify($customValues)->equals(
      [
        '1' => 'Paris',
        '2' => 'France',
      ]
    );
  }
}
