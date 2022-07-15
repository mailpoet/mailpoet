<?php

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Manage;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\Url as UrlHelper;

class ManageTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var SegmentEntity */
  private $segmentB;

  /** @var SegmentEntity */
  private $hiddenSegment;

  /** @var SegmentEntity */
  private $segmentA;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->_after();
    $segmentFactory = new SegmentFactory();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentA = $segmentFactory->withName('List A')->create();
    $this->segmentB = $segmentFactory->withName('List B')->create();
    $this->hiddenSegment = $segmentFactory->withName('Hidden List')->create();
    $this->settings->set('subscription.segments', [$this->segmentA->getId(), $this->segmentB->getId()]);
    $this->subscriber = (new SubscriberFactory())
      ->withFirstName('John')
      ->withLastName('John')
      ->withEmail('john.doe@example.com')
      ->withSegments([$this->segmentA, $this->hiddenSegment])
      ->create();
  }

  public function testItDoesntRemoveHiddenSegmentsAndCanResubscribe() {
    $manage = new Manage(
      Stub::make(UrlHelper::class, [
        'redirectBack' => null,
      ]),
      Stub::make(FieldNameObfuscator::class, [
        'deobfuscateFormPayload' => function($data) {
          return $data;
        },
      ]),
      Stub::make(LinkTokens::class, [
        'verifyToken' => function($token) {
          return true;
        },
      ]),
      $this->diContainer->get(Unsubscribes::class),
      $this->settings,
      $this->diContainer->get(NewSubscriberNotificationMailer::class),
      $this->diContainer->get(WelcomeScheduler::class),
      $this->diContainer->get(CustomFieldsRepository::class),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(SubscriberSegmentRepository::class),
      $this->diContainer->get(SubscriberSaveController::class)
    );
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [$this->segmentB->getId()],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    if (!$subscriber) throw new InvalidStateException();
    $subscriptions = $this->createSegmentsMap($subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriptions)->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);

    // Test it can resubscribe
    $_POST['data']['segments'] = [$this->segmentA->getId()];
    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    if (!$subscriber) throw new InvalidStateException();
    $subscriptions = $this->createSegmentsMap($subscriber);
    expect($subscriptions)->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  /**
   * @return array<int, array{status: string, segment_id: int}>
   */
  private function createSegmentsMap(SubscriberEntity $subscriber): array {
    $subscriptions = array_map(function(SubscriberSegmentEntity $subscriberSegment): array {
      $segment = $subscriberSegment->getSegment();
      return ['status' => $subscriberSegment->getStatus(), 'segment_id' => (int)(!$segment ?: $segment->getId())];
    }, $subscriber->getSubscriberSegments()->toArray());
    usort($subscriptions, function(array $a, array $b) {
      return $a['segment_id'] - $b['segment_id'];
    });
    return $subscriptions;
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
  }
}
