<?php

namespace MailPoet\Subscribers;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Idiorm\ORM;

class SubscriberActionsTest extends \MailPoetTest {

  /** @var array */
  private $test_data;

  /** @var SubscriberActions */
  private $subscriber_actions;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->testData = [
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ];
    $this->subscriberActions = ContainerWrapper::getInstance()->get(SubscriberActions::class);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCanSubscribe() {
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(['name' => 'List #2']);
    $segment2->save();

    $subscriber = $this->subscriberActions->subscribe(
      $this->testData,
      [$segment->id(), $segment2->id()]
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    expect($subscriber->email)->equals($this->testData['email']);
    expect($subscriber->firstName)->equals($this->testData['first_name']);
    expect($subscriber->lastName)->equals($this->testData['last_name']);
    // signup confirmation is enabled by default
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->deletedAt)->equals(null);
  }

  public function testItSchedulesWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsDisabled() {
    // create segment
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();
    expect($segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    foreach ($newsletterOptions as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = $newsletter->type;
      $newsletterOptionField->save();
      expect($newsletterOptionField->getErrors())->false();

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletter->id;
      $newsletterOption->value = (string)$value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }

    $this->settings->set('signup_confirmation.enabled', false);
    $subscriber = $this->subscriberActions->subscribe($this->testData, [$segment->id()]);
    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    $scheduledNotification = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduledNotification)->notEmpty();
  }

  public function testItDoesNotScheduleWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsEnabled() {
    // create segment
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();
    expect($segment->getErrors())->false();

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($newsletter->getErrors())->false();

    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    foreach ($newsletterOptions as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = $newsletter->type;
      $newsletterOptionField->save();
      expect($newsletterOptionField->getErrors())->false();

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $newsletter->id;
      $newsletterOption->value = (string)$value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }

    $this->settings->set('signup_confirmation.enabled', true);
    $subscriber = $this->subscriberActions->subscribe($this->testData, [$segment->id()]);
    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    $scheduledNotification = SendingQueue::findTaskByNewsletterId($newsletter->id)
      ->where('tasks.status', SendingQueue::STATUS_SCHEDULED)
      ->findOne();
    expect($scheduledNotification)->isEmpty();
  }

  public function testItCannotSubscribeWithReservedColumns() {
    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();

    $subscriber = $this->subscriberActions->subscribe(
      [
        'email' => 'donald@mailpoet.com',
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        // the fields below should NOT be taken into account
        'id' => 1337,
        'wp_user_id' => 7331,
        'is_woocommerce_user' => 1,
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'created_at' => '1984-03-09 00:00:01',
        'updated_at' => '1984-03-09 00:00:02',
        'deleted_at' => '1984-03-09 00:00:03',
      ],
      [$segment->id()]
    );

    expect($subscriber->id > 0)->equals(true);
    expect($subscriber->id)->notEquals(1337);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals('donald@mailpoet.com');
    expect($subscriber->firstName)->equals('Donald');
    expect($subscriber->lastName)->equals('Trump');

    expect($subscriber->wpUserId)->equals(null);
    expect($subscriber->isWoocommerceUser)->equals(0);
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->createdAt)->notEquals('1984-03-09 00:00:01');
    expect($subscriber->updatedAt)->notEquals('1984-03-09 00:00:02');
    expect($subscriber->createdAt)->equals($subscriber->updatedAt);
    expect($subscriber->deletedAt)->equals(null);
  }

  public function testItOverwritesSubscriberDataWhenConfirmationIsDisabled() {
    $originalSettingValue = $this->settings->get('signup_confirmation.enabled');
    $this->settings->set('signup_confirmation.enabled', false);

    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(['name' => 'List #2']);
    $segment2->save();

    $data = [
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    ];

    $subscriber = $this->subscriberActions->subscribe(
      $data,
      [$segment->id()]
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->firstName)->equals($data['first_name']);
    expect($subscriber->lastName)->equals($data['last_name']);

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    $subscriber = $this->subscriberActions->subscribe(
      $data2,
      [$segment->id(), $segment2->id()]
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    expect($subscriber->email)->equals($data2['email']);
    expect($subscriber->firstName)->equals($data2['first_name']);
    expect($subscriber->lastName)->equals($data2['last_name']);

    $this->settings->set('signup_confirmation.enabled', $originalSettingValue);
  }

  public function testItStoresUnconfirmedSubscriberDataWhenConfirmationIsEnabled() {
    $originalSettingValue = $this->settings->get('signup_confirmation.enabled');
    $this->settings->set('signup_confirmation.enabled', true);

    $segment = Segment::create();
    $segment->hydrate(['name' => 'List #1']);
    $segment->save();

    $segment2 = Segment::create();
    $segment2->hydrate(['name' => 'List #2']);
    $segment2->save();

    $data = [
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    ];

    $subscriber = $this->subscriberActions->subscribe(
      $data,
      [$segment->id()]
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(1);
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->firstName)->equals($data['first_name']);
    expect($subscriber->lastName)->equals($data['last_name']);

    expect($subscriber->unconfirmedData)->isEmpty();

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    $subscriber = $this->subscriberActions->subscribe(
      $data2,
      [$segment->id(), $segment2->id()]
    );

    expect($subscriber->id() > 0)->equals(true);
    expect($subscriber->segments()->count())->equals(2);
    // fields should be left intact
    expect($subscriber->email)->equals($data['email']);
    expect($subscriber->firstName)->equals($data['first_name']);
    expect($subscriber->lastName)->equals($data['last_name']);

    expect($subscriber->unconfirmedData)->notEmpty();
    expect($subscriber->unconfirmedData)->equals(json_encode($data2));

    // Unconfirmed data should be wiped after any direct update
    // during confirmation, manual admin editing
    $subscriber = Subscriber::createOrUpdate($data2);
    expect($subscriber->unconfirmedData)->isEmpty();
    // during import
    $subscriber->unconfirmedData = json_encode($data2);
    $subscriber->save();
    expect($subscriber->isDirty('unconfirmed_data'))->false();
    expect($subscriber->unconfirmedData)->notEmpty();
    Subscriber::updateMultiple(
      array_keys($data2),
      [array_values($data2)]
    );
    $subscriber = Subscriber::where('email', $data2['email'])->findOne();
    expect($subscriber->unconfirmedData)->isEmpty();

    $this->settings->set('signup_confirmation.enabled', $originalSettingValue);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
