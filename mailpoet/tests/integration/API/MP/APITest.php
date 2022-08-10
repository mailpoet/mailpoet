<?php

namespace MailPoet\Test\API\MP;

use Codeception\Stub;
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

class APITest extends \MailPoetTest {
  const VERSION = 'v1';

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /**  @var SegmentsRepository */
  private $segmentRepository;

  public function _before(): void {
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

  private function getApi($subscriberActions = null): API {
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
  }
}
