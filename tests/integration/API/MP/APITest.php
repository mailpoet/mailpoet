<?php

namespace MailPoet\Test\API\MP;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\CustomFields\ApiDataSanitizer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Tasks\Sending;
use MailPoetVendor\Idiorm\ORM;

class APITest extends \MailPoetTest {
  const VERSION = 'v1';

  /** @var CustomFieldsRepository */
  private $customFieldRepository;

  public function _before(): void {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', true);
    $this->customFieldRepository = $this->diContainer->get(CustomFieldsRepository::class);
  }

  private function getApi() {
    return new \MailPoet\API\MP\v1\API(
      Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      Stub::makeEmpty(ConfirmationEmailMailer::class, ['sendConfirmationEmail']),
      $this->diContainer->get(RequiredCustomFieldValidator::class),
      Stub::makeEmpty(ApiDataSanitizer::class),
      Stub::makeEmpty(WelcomeScheduler::class),
      SettingsController::getInstance()
    );
  }

  public function testItReturnsDefaultSubscriberFields() {
    $response = $this->getApi()->getSubscriberFields();

    expect($response)->contains([
      'id' => 'email',
      'name' => __('Email', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '1',
      ],
    ]);
    expect($response)->contains([
      'id' => 'first_name',
      'name' => __('First name', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '',
      ],
    ]);
    expect($response)->contains([
      'id' => 'last_name',
      'name' => __('Last name', 'mailpoet'),
      'type' => 'text',
      'params' => [
        'required' => '',
      ],
    ]);
  }

  public function testItReturnsCustomFields() {
    $customField1 = $this->customFieldRepository->createOrUpdate([
      'name' => 'text custom field',
      'type' => CustomFieldEntity::TYPE_TEXT,
      'params' => ['required' => '1', 'date_type' => 'year_month_day'],
    ]);
    $customField2 = $this->customFieldRepository->createOrUpdate([
      'name' => 'checkbox custom field',
      'type' => CustomFieldEntity::TYPE_CHECKBOX,
      'params' => ['required' => ''],
    ]);
    $response = $this->getApi()->getSubscriberFields();
    expect($response)->contains([
      'id' => 'cf_' . $customField1->getId(),
      'name' => 'text custom field',
      'type' => 'text',
      'params' => [
        'required' => '1',
        'label' => 'text custom field',
        'date_type' => 'year_month_day',
      ],
    ]);
    expect($response)->contains([
      'id' => 'cf_' . $customField2->getId(),
      'name' => 'checkbox custom field',
      'type' => 'checkbox',
      'params' => [
        'required' => '',
        'label' => 'checkbox custom field',
      ],
    ]);
  }

  public function testItDoesNotSubscribeMissingSubscriberToLists() {
    try {
      $this->getApi()->subscribeToLists(false, [1,2,3]);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function testItDoesNotSubscribeSubscriberToMissingLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [1,2,3]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [1]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
    }
  }

  public function testItDoesNotSubscribeSubscriberToWPUsersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WP_USERS,
      ]
    );
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [$segment->id]);
      $this->fail('WP Users segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WordPress Users list with ID {$segment->id}.");
    }
  }

  public function testItDoesNotSubscribeSubscriberToWooCommerceCustomersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS,
      ]
    );
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [$segment->id]);
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WooCommerce Customers list with ID {$segment->id}.");
    }
  }

  public function testItDoesNotSubscribeSubscriberToListsWhenOneOrMoreListsAreMissing() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [$segment->id, 90, 100]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, [$segment->id, 90]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  public function testItUsesMultipleListsSubscribeMethodWhenSubscribingToSingleList() {
    // subscribing to single list = converting list ID to an array and using
    // multiple lists subscription method
    $API = Stub::make($this->getApi(), [
      'subscribeToLists' => function() {
        return func_get_args();
      },
    ]);
    expect($API->subscribeToList(1, 2))->equals(
      [
        1,
        [
          2,
        ],
        [],
      ]
    );
  }

  public function testItSubscribesSubscriberToMultipleLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );

    // test if segments are specified
    try {
      $this->getApi()->subscribeToLists($subscriber->id, []);
      $this->fail('Segments are required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->id, [$segment->id]);
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
  }

  public function testItSendsConfirmationEmailToASubscriberWhenBeingAddedToList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();
    $segment = Segment::createOrUpdate([
      'name' => 'Default',
      'type' => Segment::TYPE_DEFAULT,
    ]);
    $segment->save();

    $sent = false;
    $API = $this->makeEmptyExcept(\MailPoet\API\MP\v1\API::class, 'subscribeToLists', [
      '_sendConfirmationEmail' => function () use (&$sent) {
        $sent = true;
      },
      'settings' => SettingsController::getInstance(),
    ]);

    $segments = [$segment->id];

    // should not send
    $API->subscribeToLists($subscriber->email, $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => true]);
    expect($sent)->equals(false);

    // should send
    $API->subscribeToLists($subscriber->email, $segments, ['skip_subscriber_notification' => true]);
    expect($sent)->equals(true);
  }

  public function testItSendsNotifiationEmailWhenBeingAddedToList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $segment = Segment::createOrUpdate([
      'name' => 'Default',
      'type' => Segment::TYPE_DEFAULT,
    ]);
    $segment->save();
    $segments = [$segment->id];

    // should not send
    $notificationMailer = $this->make(NewSubscriberNotificationMailer::class, ['send' => \Codeception\Stub\Expected::never()]);
    $API = new \MailPoet\API\MP\v1\API(
      $notificationMailer,
      $this->makeEmpty(ConfirmationEmailMailer::class),
      $this->makeEmpty(RequiredCustomFieldValidator::class),
      $this->makeEmpty(ApiDataSanitizer::class),
      Stub::makeEmpty(WelcomeScheduler::class),
      SettingsController::getInstance()
    );
    $API->subscribeToLists($subscriber->email, $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => true]);


    // should send
    $notificationMailer = $this->make(NewSubscriberNotificationMailer::class, ['send' => \Codeception\Stub\Expected::once()]);
    $API = new \MailPoet\API\MP\v1\API(
      $notificationMailer,
      $this->makeEmpty(ConfirmationEmailMailer::class),
      $this->makeEmpty(RequiredCustomFieldValidator::class),
      $this->makeEmpty(ApiDataSanitizer::class),
      Stub::makeEmpty(WelcomeScheduler::class),
      SettingsController::getInstance()
    );
    $API->subscribeToLists($subscriber->email, $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => false]);
  }

  public function testItSubscribesSubscriberWithEmailIdentifier() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $result = $this->getApi()->subscribeToList($subscriber->email, $segment->id);
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'])->notEmpty();
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
  }

  public function testItSchedulesWelcomeNotificationByDefaultAfterSubscriberSubscriberToLists() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::once(),
        'settings' => SettingsController::getInstance(),
      ], $this);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $API->subscribeToLists($subscriber->id, [$segment->id], ['skip_subscriber_notification' => true]);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterSubscribingSubscriberToListsWhenDisabledByOption() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'settings' => SettingsController::getInstance(),
      ], $this);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $options = ['schedule_welcome_email' => false, 'skip_subscriber_notification' => true];
    $API->subscribeToLists($subscriber->id, [$segment->id], $options);
  }

  public function testItGetsSegments() {
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $result = $this->getApi()->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($segment->id);
  }

  public function testItExcludesWPUsersAndWooCommerceCustomersSegmentsWhenGettingSegments() {
    $defaultSegment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $wpSegment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WP_USERS,
      ]
    );
    $wcSegment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS,
      ]
    );
    $result = $this->getApi()->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($defaultSegment->id);
  }

  public function testItRequiresEmailAddressToAddSubscriber() {
    try {
      $this->getApi()->addSubscriber([]);
      $this->fail('Subscriber email address required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Subscriber email address is required.');
    }
  }

  public function testItOnlyAcceptsWhitelistedProperties() {
    $subscriber = [
      'email' => 'test-ignore-status@example.com',
      'first_name' => '',
      'last_name' => '',
      'status' => 'bounced',
    ];

    $result = $this->getApi()->addSubscriber($subscriber);
    expect($result['status'])->equals('unconfirmed');
  }

  public function testItDoesNotAddExistingSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    try {
      $this->getApi()->addSubscriber(['email' => $subscriber->email]);
      $this->fail('Subscriber exists exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber already exists.');
    }
  }

  public function testItThrowsExceptionWhenSubscriberCannotBeAdded() {
    $subscriber = [
      'email' => 'test', // invalid email
    ];
    try {
      $this->getApi()->addSubscriber($subscriber);
      $this->fail('Failed to add subscriber exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('Failed to add subscriber:');
      // error message (converted to lowercase) returned by the model
      expect($e->getMessage())->stringContainsString('your email address is invalid!');
    }
  }

  public function testItAddsSubscriber() {
    $customField = $this->customFieldRepository->createOrUpdate([
      'name' => 'test custom field',
      'type' => CustomFieldEntity::TYPE_TEXT,
    ]);

    $subscriber = [
    'email' => 'test@example.com',
    'cf_' . $customField->getId() => 'test',
    ];

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $result = $this->getApi()->addSubscriber($subscriber);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['cf_' . $customField->getId()])->equals('test');
    expect($result['source'])->equals('api');
    expect($result['subscribed_ip'])->equals($_SERVER['REMOTE_ADDR']);
    expect(strlen($result['unsubscribe_token']))->equals(15);
  }

  public function testItAllowsToOverrideSubscriberIPAddress() {
    $subscriber = [
      'email' => 'test-ip-2@example.com',
      'subscribed_ip' => '1.2.3.4',
    ];

    $result = $this->getApi()->addSubscriber($subscriber);
    expect($result['subscribed_ip'])->equals($subscriber['subscribed_ip']);
  }

  public function testItChecksForMandatoryCustomFields() {
    $this->customFieldRepository->createOrUpdate([
      'name' => 'custom field',
      'type' => CustomFieldEntity::TYPE_TEXT,
      'params' => ['required' => '1'],
    ]);

    $subscriber = [
      'email' => 'test@example.com',
    ];

    $this->expectException('Exception');
    $this->getApi()->addSubscriber($subscriber);
  }

  public function testItSubscribesToSegmentsWhenAddingSubscriber() {
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $subscriber = [
      'email' => 'test@example.com',
    ];

    $result = $this->getApi()->addSubscriber($subscriber, [$segment->id]);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
  }

  public function testItSchedulesWelcomeNotificationByDefaultAfterAddingSubscriber() {
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', false);
    $API = Stub::make(
      \MailPoet\API\MP\v1\API::class,
      [
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'confirmationEmailMailer' => Stub::makeEmpty(ConfirmationEmailMailer::class),
        '_scheduleWelcomeNotification' => Expected::once(),
        'settings' => $settings,
      ],
      $this
    );
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [$segment->id];
    $API->addSubscriber($subscriber, $segments);
  }

  public function testItThrowsIfWelcomeEmailFails() {
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', false);
    $task = ScheduledTask::create();
    $task->type = 'sending';
    $task->setError("Big Error");
    $sendingStub = Sending::create($task, SendingQueue::create());
    $welcomeScheduler = $this->make('MailPoet\Newsletter\Scheduler\WelcomeScheduler', [
      'scheduleSubscriberWelcomeNotification' => [$sendingStub],
    ]);
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $API = new \MailPoet\API\MP\v1\API(
      Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      Stub::makeEmpty(ConfirmationEmailMailer::class, ['sendConfirmationEmailOnce']),
      $this->diContainer->get(RequiredCustomFieldValidator::class),
      Stub::makeEmpty(ApiDataSanitizer::class),
      $welcomeScheduler,
      Stub::makeEmpty(SettingsController::class)
    );
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [$segment->id()];
    $this->expectException('\Exception');
    $API->addSubscriber($subscriber, $segments, ['schedule_welcome_email' => true, 'send_confirmation_email' => false]);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberIfStatusIsNotSubscribed() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(
          NewSubscriberNotificationMailer::class, ['send' => Expected::never()]
        ),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ], $this);
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [1];
    $API->addSubscriber($subscriber, $segments);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberWhenDisabledByOption() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ], $this);
    $subscriber = [
      'email' => 'test@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ];
    $segments = [1];
    $options = ['schedule_welcome_email' => false];
    $API->addSubscriber($subscriber, $segments, $options);
  }

  public function testByDefaultItSendsConfirmationEmailAfterAddingSubscriber() {
    $API = $this->makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      [
        'subscribeToLists' => Expected::once(function ($subscriberId, $segmentsIds, $options) {
          expect($options)->contains('send_confirmation_email');
          expect($options['send_confirmation_email'])->equals(true);
        }),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      ]
    );
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [1];
    $API->addSubscriber($subscriber, $segments);
  }

  public function testItThrowsWhenConfirmationEmailFailsToSend() {
    $confirmationMailer = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationMailer->expects($this->once())
      ->method('sendConfirmationEmailOnce')
      ->willReturnCallback(function (Subscriber $subscriber) {
        $subscriber->setError('Big Error');
        return false;
      });

    $API = Stub::copy($this->getApi(), [
      'confirmationEmailMailer' => $confirmationMailer,
    ]);
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $this->expectException('\Exception');
    $this->expectExceptionMessage('Subscriber added to lists, but confirmation email failed to send: big error');
    $API->addSubscriber($subscriber, [$segment->id], ['send_confirmation_email' => true]);
  }

  public function testItDoesNotSendConfirmationEmailAfterAddingSubscriberWhenOptionIsSet() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      [
        '__sendConfirmationEmail' => Expected::never(),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      ], $this);
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [1];
    $options = ['send_confirmation_email' => false];
    $API->addSubscriber($subscriber, $segments, $options);
  }

  public function testItRequiresNameToAddList() {
    try {
      $this->getApi()->addList([]);
      $this->fail('List name required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List name is required.');
    }
  }

  public function testItDoesOnlySaveWhiteListedPropertiesWhenAddingList() {
    $result = $this->getApi()->addList([
      'name' => 'Test segment123',
      'description' => 'Description',
      'type' => 'ignore this field',
    ]);
    expect($result['id'])->greaterThan(0);
    expect($result['name'])->equals('Test segment123');
    expect($result['description'])->equals('Description');
    expect($result['type'])->equals('default');
  }

  public function testItDoesNotAddExistingList() {
    $segment = Segment::create();
    $segment->name = 'Test segment';
    $segment->save();
    try {
      $this->getApi()->addList(['name' => $segment->name]);
      $this->fail('List exists exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list already exists.');
    }
  }

  public function testItAddsList() {
    $segment = [
      'name' => 'Test segment',
    ];

    $result = $this->getApi()->addList($segment);
    expect($result['id'])->greaterThan(0);
    expect($result['name'])->equals($segment['name']);
  }

  public function testItDoesNotUnsubscribeMissingSubscriberFromLists() {
    try {
      $this->getApi()->unsubscribeFromLists(false, [1,2,3]);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromMissingLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [1,2,3]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [1]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromListsWhenOneOrMoreListsAreMissing() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [$segment->id, 90, 100]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [$segment->id, 90]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromWPUsersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WP_USERS,
      ]
    );
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [$segment->id]);
      $this->fail('WP Users segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't unsubscribe from a WordPress Users list with ID {$segment->id}.");
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromWooCommerceCustomersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS,
      ]
    );
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, [$segment->id]);
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't unsubscribe from a WooCommerce Customers list with ID {$segment->id}.");
    }
  }

  public function testItUsesMultipleListsUnsubscribeMethodWhenUnsubscribingFromSingleList() {
    // unsubscribing from single list = converting list ID to an array and using
    // multiple lists unsubscribe method
    $API = Stub::make(\MailPoet\API\MP\v1\API::class, [
      'unsubscribeFromLists' => function() {
        return func_get_args();
      },
    ]);
    expect($API->unsubscribeFromList(1, 2))
      ->equals([
        1,
        [
          2,
        ],
      ]
    );
  }

  public function testItUnsubscribesSubscriberFromMultipleLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );

    // test if segments are specified
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, []);
      $this->fail('Segments are required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->id, [$segment->id]);
    expect($result['subscriptions'][0]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    $result = $this->getApi()->unsubscribeFromLists($subscriber->id, [$segment->id]);
    expect($result['subscriptions'][0]['status'])->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItGetsSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      [
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT,
      ]
    );
    $this->getApi()->subscribeToList($subscriber->id, $segment->id);

    // successful response
    $result = $this->getApi()->getSubscriber($subscriber->email);
    expect($result['email'])->equals($subscriber->email);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);

    // error response
    try {
      $this->getApi()->getSubscriber('some_fake_email');
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    $this->truncateEntity(CustomFieldEntity::class);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
