<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\APIException;
use MailPoet\API\MP\v1\CustomFields;
use MailPoet\API\MP\v1\Segments;
use MailPoet\API\MP\v1\Subscribers;
use MailPoet\Config\Changelog;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class SubscribersTest extends \MailPoetTest {


  /**  @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var CustomFieldsRepository */
  private $customFieldRepository;

  public function _before() {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', true);
    $this->subscriberFactory = new SubscriberFactory();
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->customFieldRepository = $this->diContainer->get(CustomFieldsRepository::class);
  }

  private function getSubscribers() {
    return $this->getServiceWithOverrides(
      Subscribers::class,
      [
        'confirmationEmailMailer' => Stub::makeEmpty(ConfirmationEmailMailer::class, ['sendConfirmationEmail']),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'welcomeScheduler' => Stub::makeEmpty(WelcomeScheduler::class),
      ]
    );
  }

  private function getApi($subscriberActions = null) {
    if (!$subscriberActions) {
      $subscriberActions = $this->getSubscribers();
    }
    return new API(
      $this->diContainer->get(CustomFields::class),
      $this->diContainer->get(Segments::class),
      $subscriberActions,
      $this->diContainer->get(Changelog::class)
    );
  }

  private function getSegment($name = 'Default', $type = SegmentEntity::TYPE_DEFAULT) {
    $segment = $this->segmentRepository->createOrUpdate($name);
    $segment->setType($type);
    $this->segmentRepository->flush();
    return $segment;
  }

  public function testItDoesNotSubscribeMissingSubscriberToLists() {
    try {
      $this->getApi()->subscribeToLists(false, [1,2,3]);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('A subscriber is required.');
    }
  }

  public function testItDoesNotSubscribeSubscriberToMissingLists() {
    $subscriber = $this->subscriberFactory->create();
    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [1,2,3]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [1]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
    }
  }

  public function testItDoesNotSubscribeSubscriberToWPUsersList() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment('WordPress', SegmentEntity::TYPE_WP_USERS);

    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId()]);
      $this->fail('WP Users segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WordPress Users list with ID {$segment->getId()}.");
    }
  }

  public function testItDoesNotSubscribeSubscriberToWooCommerceCustomersList() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment('WooCommerce', SegmentEntity::TYPE_WC_USERS);

    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId()]);
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WooCommerce Customers list with ID {$segment->getId()}.");
    }
  }

  public function testItDoesNotSubscribeSubscriberToListsWhenOneOrMoreListsAreMissing() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();

    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId(), 90, 100]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId(), 90]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  public function testItSubscribesSubscriberToMultipleLists() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();

    // test if segments are specified
    try {
      $this->getApi()->subscribeToLists($subscriber->getId(), []);
      $this->fail('Segments are required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId()]);
    expect($result['id'])->equals($subscriber->getId());
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->getId());
  }

  public function testItSendsConfirmationEmailToASubscriberWhenBeingAddedToList() {
    $subscriber = $this->subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $segment = $this->getSegment();
    $segments = [$segment->getId()];

    $sent = false;
    $subscribers = $this->makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_sendConfirmationEmail' => function () use (&$sent) {
          $sent = true;
        },
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ]
    );

    $API = $this->getApi($subscribers);

    $API->subscribeToLists($subscriber->getEmail(), $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => true]);
    expect($sent)->equals(false);
    // should send
    $API->subscribeToLists($subscriber->getEmail(), $segments, ['skip_subscriber_notification' => true]);
    expect($sent)->equals(true);
  }

  public function testItSendsNotificationEmailWhenBeingAddedToList() {
    $subscriber = $this->subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $segment = $this->getSegment();
    $segments = [$segment->getId()];

    $subscriberActions = Stub::copy($this->getSubscribers(), [
      '$newSubscriberNotificationMailer' => $this->make(NewSubscriberNotificationMailer::class, ['send' => Expected::never()]),
    ]);
    $API = $this->getApi($subscriberActions);

    $API->subscribeToLists($subscriber->getEmail(), $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => true]);

    // should send
    $subscribers = Stub::copy($this->getSubscribers(), [
      'newSubscriberNotificationMailer' => $this->make(NewSubscriberNotificationMailer::class, ['send' => Expected::once()]),
    ]);
    $API = $this->getApi($subscribers);

    $API->subscribeToLists($subscriber->getEmail(), $segments, ['send_confirmation_email' => false, 'skip_subscriber_notification' => false]);
  }

  public function testItSubscribesSubscriberWithEmailIdentifier() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();

    $result = $this->getApi()->subscribeToList($subscriber->getEmail(), $segment->getId());
    expect($result['id'])->equals($subscriber->getId());
    expect($result['subscriptions'])->notEmpty();
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->getId());
  }

  public function testItSchedulesWelcomeNotificationByDefaultAfterSubscriberSubscriberToLists() {
    $subscriber = $this->subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $segment = $this->getSegment();

    $subscribers = Stub::makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::once(),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ],
      $this);
    $API = $this->getApi($subscribers);

    $API->subscribeToLists($subscriber->getId(), [$segment->getId()], ['skip_subscriber_notification' => true]);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterSubscribingSubscriberToListsWhenDisabledByOption() {
    $subscriber = $this->subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $segment = $this->getSegment();
    $options = ['schedule_welcome_email' => false, 'skip_subscriber_notification' => true];

    $subscribers = Stub::makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ],
      $this);
    $API = $this->getApi($subscribers);


    $API->subscribeToLists($subscriber->getId(), [$segment->getId()], $options);
  }

  public function testUnsubscribeRaisesExceptionWhenSubscriberIdIsNotPassed() {
    try {
      $this->getApi()->unsubscribe(false);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('A subscriber is required.');
    }
  }

  public function testUnsubscribeRaisesExceptionWhenSubscriberDoesNotExist() {
    try {
      $this->getApi()->unsubscribe('asdf');
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function testUnsubscribeRaisesExceptionIfSubscriberAlreadyUnsubscribed() {
    $subscriber = $this->subscriberFactory
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    try {
      $this->getApi()->unsubscribe($subscriber->getId());
      $this->fail('Subscriber already unsubscribed exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber is already unsubscribed.');
    }
  }

  public function testUnsubscribesSubscriberFromAllListsAndChangesItsStatus() {
    $subscriber = $this->subscriberFactory->create();
    $segment1 = $this->getSegment('Segment 1');
    $segment2 = $this->getSegment('Segment 2');
    $this->getApi()->subscribeToLists($subscriber->getId(), [$segment1->getId(), $segment2->getId()]);
    $this->assertSame(SubscriberEntity::STATUS_SUBSCRIBED, $subscriber->getStatus());

    $result = $this->getApi()->unsubscribe($subscriber->getId());
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriber->getStatus());

    foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
      $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberSegment->getStatus());
    }

    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['status']);
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['subscriptions'][0]['status']);
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['subscriptions'][1]['status']);
  }

  public function testUnsubscribesSubscriberFromAllListsAndChangesItsStatusUsingEmailInsteadOfId() {
    $subscriber = $this->subscriberFactory->create();
    $segment1 = $this->getSegment('Segment 1');
    $segment2 = $this->getSegment('Segment 2');
    $this->getApi()->subscribeToLists($subscriber->getId(), [$segment1->getId(), $segment2->getId()]);
    $this->assertSame(SubscriberEntity::STATUS_SUBSCRIBED, $subscriber->getStatus());

    $result = $this->getApi()->unsubscribe($subscriber->getEmail());
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriber->getStatus());

    foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
      $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberSegment->getStatus());
    }

    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['status']);
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['subscriptions'][0]['status']);
    $this->assertSame(SubscriberEntity::STATUS_UNSUBSCRIBED, $result['subscriptions'][1]['status']);
  }

  public function testItDoesNotUnsubscribeWhenSubscriberIdNotPassedFromLists() {
    try {
      $this->getApi()->unsubscribeFromLists(false, [1,2,3]);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('A subscriber is required.');
    }
  }

  public function testItDoesNotUnsubscribeMissingSubscriberFromLists() {
    try {
      $this->getApi()->unsubscribeFromLists('asdf', [1,2,3]);
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromMissingLists() {
    $subscriber = $this->subscriberFactory->create();
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [1,2,3]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [1]);
      $this->fail('Missing segments exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromListsWhenOneOrMoreListsAreMissing() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [$segment->getId(), 90, 100]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [$segment->getId(), 90]);
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromWPUsersList() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment('WordPress', SegmentEntity::TYPE_WP_USERS);
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [$segment->getId()]);
      $this->fail('WP Users segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't unsubscribe from a WordPress Users list with ID {$segment->getId()}.");
    }
  }

  public function testItDoesNotUnsubscribeSubscriberFromWooCommerceCustomersList() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment('Default', SegmentEntity::TYPE_WC_USERS);

    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), [$segment->getId()]);
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals("Can't unsubscribe from a WooCommerce Customers list with ID {$segment->getId()}.");
    }
  }

  public function testItUnsubscribesSubscriberFromMultipleLists() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();

    // test if segments are specified
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->getId(), []);
      $this->fail('Segments are required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->getId(), [$segment->getId()]);
    expect($result['subscriptions'][0]['status'])->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    $result = $this->getApi()->unsubscribeFromLists($subscriber->getId(), [$segment->getId()]);
    expect($result['subscriptions'][0]['status'])->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItChecksEmptyParamsInCorrectOrder() {
    // test if segments are specified
    try {
      $this->getApi()->unsubscribeFromLists(null, []);
      $this->fail('Segments are required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    // test if segments are specified
    try {
      $this->getApi()->unsubscribeFromLists(null, [1]);
      $this->fail('Subscriber is required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('A subscriber is required.');
    }
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
    $subscriber = $this->subscriberFactory->create();
    try {
      $this->getApi()->addSubscriber(['email' => $subscriber->getEmail()]);
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
      expect($e->getMessage())->stringContainsString('value is not a valid email address.');
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

    $expectedRemoteAddr = '127.0.0.1';
    $_SERVER['REMOTE_ADDR'] = $expectedRemoteAddr;
    $result = $this->getApi()->addSubscriber($subscriber);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['cf_' . $customField->getId()])->equals('test');
    expect($result['source'])->equals('api');
    expect($result['subscribed_ip'])->equals($expectedRemoteAddr);
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
    $this->expectExceptionMessage('Missing value for custom field "custom field');
    $this->getApi()->addSubscriber($subscriber);
  }

  public function testItSubscribesToSegmentsWhenAddingSubscriber() {
    $segment = $this->getSegment();
    $subscriber = [
      'email' => 'test@example.com',
    ];

    $result = $this->getApi()->addSubscriber($subscriber, [$segment->getId()]);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->getId());
  }

  public function testItSchedulesWelcomeNotificationByDefaultAfterAddingSubscriber() {
    $segment = $this->getSegment();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', false);

    $subscriberActions = Stub::make(
      Subscribers::class,
      [
        '_scheduleWelcomeNotification' => Expected::once(),
        'newSubscriberNotificationMailer' => Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'confirmationEmailMailer' => Stub::makeEmpty(ConfirmationEmailMailer::class),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscriberSaveController' => $this->diContainer->get(SubscriberSaveController::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => $settings,
        'requiredCustomFieldsValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ],
      $this);

    $API = Stub::make(
      API::class,
      [
        'subscribers' => $subscriberActions,
      ],
      $this
    );

    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [$segment->getId()];
    $API->addSubscriber($subscriber, $segments);
  }

  public function testItThrowsIfWelcomeEmailFails() {
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', false);
    $task = ScheduledTask::create();
    $task->type = 'sending';
    $task->setError("Big Error");
    $sendingStub = Sending::create($task, SendingQueue::create());
    $segment = $this->getSegment();

    $subscribers = Stub::copy($this->getSubscribers(), [
      'welcomeScheduler' => $this->make('MailPoet\Newsletter\Scheduler\WelcomeScheduler', [
        'scheduleSubscriberWelcomeNotification' => [$sendingStub],
      ]),
    ]);
    $API = $this->getApi($subscribers);

    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [$segment->getId()];
    $this->expectException('\Exception');
    $API->addSubscriber($subscriber, $segments, ['schedule_welcome_email' => true, 'send_confirmation_email' => false]);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberIfStatusIsNotSubscribed() {
    $subscribers = Stub::makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ],
      $this);
    $API = Stub::makeEmptyExcept(
      API::class,
      'addSubscriber',
      [
        'subscribers' => $subscribers,
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ], $this);
    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [1];
    $API->addSubscriber($subscriber, $segments);
  }

  public function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberWhenDisabledByOption() {
    $subscribers = Stub::makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_scheduleWelcomeNotification' => Expected::never(),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ],
      $this);
    $API = Stub::makeEmptyExcept(
      API::class,
      'addSubscriber',
      [
        'subscribers' => $subscribers,
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ], $this);
    $subscriber = [
      'email' => 'test@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ];
    $segments = [1];
    $options = ['schedule_welcome_email' => false];
    $API->addSubscriber($subscriber, $segments, $options);
  }

  public function testByDefaultItSendsConfirmationEmailAfterAddingSubscriber() {
    $subscriberActions = Stub::make(
      Subscribers::class,
      [
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscriberSaveController' => $this->diContainer->get(SubscriberSaveController::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => $this->diContainer->get(SettingsController::class),
        'requiredCustomFieldsValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'subscribeToLists' => Expected::once(function ($subscriberId, $segmentsIds, $options) {
          expect($options)->contains('send_confirmation_email');
          expect($options['send_confirmation_email'])->equals(true);
          return [];
        }),
      ],
      $this);
    $API = $this->makeEmptyExcept(
      API::class,
      'addSubscriber',
      [
        'subscribers' => $subscriberActions,
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
      ->willThrowException(new \Exception('Something went wrong with your subscription. Please contact the website owner.'));

    $subscribers = Stub::copy($this->getSubscribers(), [
      'confirmationEmailMailer' => $confirmationMailer,
    ]);
    $API = $this->getApi($subscribers);

    $segment = $this->getSegment();

    $subscriber = [
      'email' => 'test@example.com',
    ];
    $this->expectException('\Exception');
    $this->expectExceptionMessage('Subscriber added to lists, but confirmation email failed to send: something went wrong with your subscription. please contact the website owner.');
    $API->addSubscriber($subscriber, [$segment->getId()], ['send_confirmation_email' => true]);
  }

  public function testItDoesNotSendConfirmationEmailAfterAddingSubscriberWhenOptionIsSet() {
    $subscribers = Stub::makeEmptyExcept(
      Subscribers::class,
      'subscribeToLists',
      [
        '_sendConfirmationEmail' => Expected::never(),
        'segmentsRepository' => $this->diContainer->get(SegmentsRepository::class),
        'subscribersRepository' => $this->diContainer->get(SubscribersRepository::class),
        'subscribersSegmentRepository' => $this->diContainer->get(SubscriberSegmentRepository::class),
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => SettingsController::getInstance(),
      ],
      $this);
    $API = Stub::makeEmptyExcept(
      API::class,
      'addSubscriber',
      [
        'subscribers' => $subscribers,
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ], $this);

    $subscriber = [
      'email' => 'test@example.com',
    ];
    $segments = [1];
    $options = ['send_confirmation_email' => false];
    $API->addSubscriber($subscriber, $segments, $options);
  }

  public function testItGetsSubscriber() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();
    $this->getApi()->subscribeToList($subscriber->getId(), $segment->getId());

    $result = $this->getApi()->getSubscriber($subscriber->getEmail());
    expect($result['email'])->equals($subscriber->getEmail());
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->getId());
  }

  public function testGetSubscriberThrowsWhenSubscriberDoesntExist() {
    $this->expectException(APIException::class);
    $this->expectExceptionMessage('This subscriber does not exist.');
    $this->getApi()->getSubscriber('some_fake_email');
  }

  public function testItGetsSubscribersWithLimitAndOffset(): void {
    $subscriber1 = $this->subscriberFactory->withEmail('subscriber1@test.com')->create();
    $subscriber2 = $this->subscriberFactory->withEmail('subscriber2@test.com')->create();

    $subscribers = $this->getApi()->getSubscribers([], 1, 0);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber1->getEmail(), $subscribers[0]['email']);

    $subscribers = $this->getApi()->getSubscribers([], 1, 1);
    $this->assertEquals($subscriber2->getEmail(), $subscribers[0]['email']);
  }

  public function testItFiltersSubscribersByMinimalUpdatedAt(): void {
    $subscriber1 = $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withUpdatedAt(new Carbon('2022-10-10 14:00:00'))
      ->create();
    $subscriber2 = $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withUpdatedAt(new Carbon('2022-10-10 16:00:00'))
      ->create();

    $minUpdatedAt = new Carbon('2022-10-10 15:00:00');
    // test timestamp support
    $subscribers = $this->getApi()->getSubscribers(['minUpdatedAt' => $minUpdatedAt->getTimestamp()]);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber2->getEmail(), $subscribers[0]['email']);
    // test DateTime support
    $subscribers = $this->getApi()->getSubscribers(['minUpdatedAt' => $minUpdatedAt]);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber2->getEmail(), $subscribers[0]['email']);
  }

  public function testItFiltersSubscribersByStatus(): void {
    $subscriber1 = $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $subscriber2 = $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriber3 = $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $subscribers = $this->getApi()->getSubscribers(['status' => SubscriberEntity::STATUS_SUBSCRIBED]);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber3->getEmail(), $subscribers[0]['email']);
  }

  public function testItFiltersSubscribersByListId(): void {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $subscriber1 = $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withSegments([$segment1])
      ->create();
    $subscriber2 = $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withSegments([$segment1, $segment2])
      ->create();
    $subscriber3 = $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withSegments([$segment2])
      ->create();
    $subscriber4 = $this->subscriberFactory
      ->withEmail('subscriber4@test.com')
      ->withSegments([])
      ->create();

    $subscribers = $this->getApi()->getSubscribers(['listId' => $segment2->getId()]);
    $this->assertCount(2, $subscribers);
    $this->assertEquals($subscriber2->getEmail(), $subscribers[0]['email']);
    $this->assertEquals($subscriber3->getEmail(), $subscribers[1]['email']);
  }

  public function testItFiltersSubscribersByListIdAndMinUpdatedAt(): void {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $subscriber1 = $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withSegments([$segment1])
      ->withUpdatedAt(new Carbon('2022-10-10 13:00:00'))
      ->create();
    $subscriber2 = $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withSegments([$segment1, $segment2])
      ->withUpdatedAt(new Carbon('2022-10-10 13:00:00'))
      ->create();
    $subscriber3 = $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withSegments([$segment2])
      ->withUpdatedAt(new Carbon('2022-10-11 13:00:00'))
      ->create();
    $subscriber4 = $this->subscriberFactory
      ->withEmail('subscriber4@test.com')
      ->withUpdatedAt(new Carbon('2022-10-10 13:00:00'))
      ->withSegments([])
      ->create();

    $subscribers = $this->getApi()->getSubscribers([
      'listId' => $segment2->getId(),
      'minUpdatedAt' => new Carbon('2022-10-11 12:00:00'),
    ]);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber3->getEmail(), $subscribers[0]['email']);
  }

  public function testItFiltersSubscribersByListIdAndStatus(): void {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $subscriber1 = $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withSegments([$segment1])
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriber2 = $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withSegments([$segment1, $segment2])
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subscriber3 = $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withSegments([$segment2])
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subscriber4 = $this->subscriberFactory
      ->withEmail('subscriber4@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([])
      ->create();

    $subscribers = $this->getApi()->getSubscribers([
      'listId' => $segment1->getId(),
      'status' => SubscriberEntity::STATUS_UNSUBSCRIBED,
    ]);
    $this->assertCount(1, $subscribers);
    $this->assertEquals($subscriber1->getEmail(), $subscribers[0]['email']);
  }

  public function testItGetsSubscribersCount(): void {
    $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    $count = $this->getApi()->getSubscribersCount([]);
    $this->assertEquals(2, $count);
  }

  public function testItGetsSubscribersCountByMinimalUpdatedAt(): void {
    $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withUpdatedAt(new Carbon('2022-10-10 14:00:00'))
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withUpdatedAt(new Carbon('2022-10-10 16:00:00'))
      ->create();

    $minUpdatedAt = new Carbon('2022-10-10 15:00:00');
    // test timestamp support
    $count = $this->getApi()->getSubscribersCount(['minUpdatedAt' => $minUpdatedAt->getTimestamp()]);
    $this->assertEquals(1, $count);
    // test DateTime support
    $count = $this->getApi()->getSubscribersCount(['minUpdatedAt' => $minUpdatedAt]);
    $this->assertEquals(1, $count);
  }

  public function testItGetsSubscribersCountByStatus(): void {
    $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $this->assertEquals(1, $this->getApi()->getSubscribersCount(['status' => SubscriberEntity::STATUS_UNCONFIRMED]));
    $this->assertEquals(0, $this->getApi()->getSubscribersCount(['status' => SubscriberEntity::STATUS_UNSUBSCRIBED]));
    $this->assertEquals(2, $this->getApi()->getSubscribersCount(['status' => SubscriberEntity::STATUS_SUBSCRIBED]));
  }

  public function testItGetsSubscribersCountByListId(): void {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withSegments([$segment1])
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withSegments([$segment1, $segment2])
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withSegments([])
      ->create();

    $this->assertEquals(2, $this->getApi()->getSubscribersCount(['listId' => $segment1->getId()]));
    $this->assertEquals(1, $this->getApi()->getSubscribersCount(['listId' => $segment2->getId()]));
  }

  public function testItGetsSubscribersCountByListIdAndStatus(): void {
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $this->subscriberFactory
      ->withEmail('subscriber1@test.com')
      ->withSegments([$segment1])
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber2@test.com')
      ->withSegments([$segment1, $segment2])
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber3@test.com')
      ->withSegments([$segment2])
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $this->subscriberFactory
      ->withEmail('subscriber4@test.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([])
      ->create();

    $count = $this->getApi()->getSubscribersCount([
      'listId' => $segment1->getId(),
      'status' => SubscriberEntity::STATUS_UNSUBSCRIBED,
    ]);
    $this->assertEquals(1, $count);
  }
}
