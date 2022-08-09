<?php

namespace MailPoet\Test\API\MP;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\CustomFields;
use MailPoet\API\MP\v1\Segments;
use MailPoet\API\MP\v1\Subscribers;
use MailPoet\Config\Changelog;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class APITest extends \MailPoetTest {
  const VERSION = 'v1';

  /** @var CustomFieldsRepository */
  private $customFieldRepository;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /**  @var SegmentsRepository */
  private $segmentRepository;

  public function _before(): void {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', true);
    $this->customFieldRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->subscriberFactory = new SubscriberFactory();
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  private function getSubscribers() {
    return new Subscribers(
      Stub::makeEmpty(ConfirmationEmailMailer::class, ['sendConfirmationEmail']),
      Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      $this->diContainer->get(SegmentsRepository::class),
      SettingsController::getInstance(),
      $this->diContainer->get(SubscriberSegmentRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscribersResponseBuilder::class),
      Stub::makeEmpty(WelcomeScheduler::class),
      $this->diContainer->get(FeaturesController::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }

  private function getApi($subscriberActions = null): API {
    if (!$subscriberActions) {
      $subscriberActions = $this->getSubscribers();
    }
    return new API(
      $this->diContainer->get(RequiredCustomFieldValidator::class),
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
        'subscribersResponseBuilder' => $this->diContainer->get(SubscribersResponseBuilder::class),
        'settings' => $settings,
      ],
      $this);

    $API = Stub::make(
      API::class,
      [
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
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
    $API = $this->makeEmptyExcept(
      API::class,
      'addSubscriber',
      [
        'subscribeToLists' => Expected::once(function ($subscriberId, $segmentsIds, $options) {
          expect($options)->contains('send_confirmation_email');
          expect($options['send_confirmation_email'])->equals(true);
        }),
        'requiredCustomFieldValidator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
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

  public function testItUsesMultipleListsUnsubscribeMethodWhenUnsubscribingFromSingleList() {
    // unsubscribing from single list = converting list ID to an array and using
    // multiple lists unsubscribe method
    $API = Stub::make(API::class, [
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

  public function testItGetsSubscriber() {
    $subscriber = $this->subscriberFactory->create();
    $segment = $this->getSegment();
    $this->getApi()->subscribeToList($subscriber->getId(), $segment->getId());

    // successful response
    $result = $this->getApi()->getSubscriber($subscriber->getEmail());
    expect($result['email'])->equals($subscriber->getEmail());
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->getId());

    // error response
    try {
      $this->getApi()->getSubscriber('some_fake_email');
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
