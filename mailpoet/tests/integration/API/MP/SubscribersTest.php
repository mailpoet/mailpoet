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
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersTest extends \MailPoetTest {


  /**  @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SegmentsRepository */
  private $segmentRepository;

  public function _before() {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', true);
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
      $this->diContainer->get(SubscriberSaveController::class),
      $this->diContainer->get(SubscribersResponseBuilder::class),
      Stub::makeEmpty(WelcomeScheduler::class),
      $this->diContainer->get(FeaturesController::class),
      $this->diContainer->get(RequiredCustomFieldValidator::class),
      $this->diContainer->get(WPFunctions::class)
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
        'featuresController' => $this->diContainer->get(FeaturesController::class),
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

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
