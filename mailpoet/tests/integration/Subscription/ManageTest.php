<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use Codeception\Stub;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Manage;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\Url as UrlHelper;

class ManageTest extends \MailPoetTest {
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
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentA = $segmentFactory->withName('List A')->create();
    $this->segmentB = $segmentFactory->withName('List B')->create();
    $this->hiddenSegment = $segmentFactory->withName('Hidden List')->withDisplayInManageSubscriptionPage(false)->create();
    $this->subscriber = (new SubscriberFactory())
      ->withFirstName('John')
      ->withLastName('John')
      ->withEmail('john.doe@example.com')
      ->withSegments([$this->segmentA, $this->hiddenSegment])
      ->create();
  }

  public function testItDoesntRemoveHiddenSegmentsAndCanResubscribe() {
    $manage = $this->getServiceWithOverrides(Manage::class, [
      'urlHelper' => Stub::make(UrlHelper::class, [
        'redirectBack' => null,
      ]),
      'fieldNameObfuscator' => Stub::make(FieldNameObfuscator::class, [
        'deobfuscateFormPayload' => function($data) {
          return $data;
        },
      ]),
      'linkTokens' => Stub::make(LinkTokens::class, [
        'verifyToken' => function($token) {
          return true;
        },
      ]),
    ]);
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
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriptions = $this->createSegmentsMap($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriptions)->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);

    // Test it can resubscribe
    $_POST['data']['segments'] = [$this->segmentA->getId()];
    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriptions = $this->createSegmentsMap($subscriber);
    verify($subscriptions)->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testItUsesSegmentChoicesAndIgnoresLegacySegmentsWhenPresent() {
    $manage = $this->getManageService();
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segment_choices' => [
        (string)$this->segmentA->getId() => 'unsubscribed',
      ],
      'segments' => [$this->segmentB->getId()],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testItDoesNotUpdateSegmentsWhenGloballyUnsubscribing(): void {
    $manage = $this->getManageService();
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_UNSUBSCRIBED,
      'segment_choices' => [
        (string)$this->segmentA->getId() => 'unsubscribed',
        (string)$this->segmentB->getId() => 'subscribed',
      ],
      'segments' => [$this->segmentB->getId()],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testItIgnoresInvalidHiddenDeletedAndUnknownSegmentChoices() {
    $hiddenSegment = (new SegmentFactory())
      ->withName('Other Hidden List')
      ->withDisplayInManageSubscriptionPage(false)
      ->create();
    $deletedSegment = (new SegmentFactory())
      ->withName('Deleted List')
      ->withDeleted()
      ->create();
    $dynamicSegment = (new SegmentFactory())
      ->withName('Dynamic List')
      ->withType(SegmentEntity::TYPE_DYNAMIC)
      ->create();
    $notifications = [];
    $manage = $this->getManageService([
      'newSubscriberNotificationMailer' => Stub::make(NewSubscriberNotificationMailer::class, [
        'send' => function() use (&$notifications) {
          $notifications[] = 'mail';
        },
      ]),
      'welcomeScheduler' => Stub::make(WelcomeScheduler::class, [
        'scheduleSubscriberWelcomeNotification' => function() use (&$notifications) {
          $notifications[] = 'welcome';
        },
      ]),
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segment_choices' => [
        (string)$hiddenSegment->getId() => 'subscribed',
        (string)$deletedSegment->getId() => 'subscribed',
        (string)$dynamicSegment->getId() => 'subscribed',
        '999999999' => 'subscribed',
        (string)$this->segmentB->getId() => 'invalid',
      ],
      'segments' => [$this->segmentB->getId()],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
    verify($notifications)->empty();
  }

  public function testMalformedSegmentChoiceIdsCannotChangeVisibleLists() {
    $manage = $this->getManageService();
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segment_choices' => [
        $this->segmentA->getId() . 'abc' => 'unsubscribed',
        $this->segmentB->getId() . '.9' => 'subscribed',
        $this->segmentB->getId() . 'e0' => 'subscribed',
        ' ' . $this->segmentB->getId() => 'subscribed',
        $this->segmentB->getId() . ' ' => 'subscribed',
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testMalformedLegacySegmentIdsCannotChangeVisibleLists() {
    $manage = $this->getManageService();
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segmentA->getId() . 'abc',
        $this->segmentB->getId() . '.9',
        $this->segmentB->getId() . 'e0',
        ' ' . $this->segmentB->getId(),
        $this->segmentB->getId() . ' ',
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testLegacyPositiveIdsForHiddenDeletedAndUnknownSegmentsDoNotChangeVisibleLists() {
    $deletedSegment = (new SegmentFactory())
      ->withName('Deleted List')
      ->withDeleted()
      ->create();
    $manage = $this->getManageService();
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $this->hiddenSegment->getId(),
        $deletedSegment->getId(),
        999999999,
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testObfuscatedMalformedLegacySegmentIdsCannotChangeVisibleLists() {
    $fieldNameObfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $manage = $this->getManageService([
      'fieldNameObfuscator' => $fieldNameObfuscator,
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => '',
      $fieldNameObfuscator->obfuscate('segments') => [
        ' ' . $this->segmentB->getId(),
        $this->segmentB->getId() . ' ',
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testObfuscatedLegacySegmentIdsCanChangeVisibleLists() {
    $fieldNameObfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $manage = $this->getManageService([
      'fieldNameObfuscator' => $fieldNameObfuscator,
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => '',
      $fieldNameObfuscator->obfuscate('segments') => [
        (string)$this->segmentB->getId(),
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
  }

  public function testSegmentChoicesDoNotChangeGlobalStatusUnlessPosted() {
    $subscriber = (new SubscriberFactory())
      ->withFirstName('Jane')
      ->withLastName('Jane')
      ->withEmail('jane.doe@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $notifications = [];
    $manage = $this->getManageService([
      'newSubscriberNotificationMailer' => Stub::make(NewSubscriberNotificationMailer::class, [
        'send' => function() use (&$notifications) {
          $notifications[] = 'mail';
        },
      ]),
      'welcomeScheduler' => Stub::make(WelcomeScheduler::class, [
        'scheduleSubscriberWelcomeNotification' => function() use (&$notifications) {
          $notifications[] = 'welcome';
        },
      ]),
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'Jane',
      'last_name' => 'Jane',
      'email' => 'jane.doe@example.com',
      'segment_choices' => [
        (string)$this->segmentB->getId() => 'subscribed',
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentB->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
    verify($notifications)->empty();
  }

  public function testItRedirectsWithErrorAndDoesNotSaveWhenTokenVerificationFails(): void {
    $redirectParams = null;
    $manage = $this->getManageService([
      'urlHelper' => Stub::make(UrlHelper::class, [
        'redirectBack' => function($params = []) use (&$redirectParams) {
          $redirectParams = $params;
        },
      ]),
      'linkTokens' => Stub::make(LinkTokens::class, [
        'verifyToken' => function() {
          return false;
        },
      ]),
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'stale-token';
    $_POST['data'] = [
      'first_name' => 'Changed',
      'last_name' => 'Changed',
      'email' => 'john.doe@example.com',
      'status' => SubscriberEntity::STATUS_UNSUBSCRIBED,
      'segment_choices' => [
        (string)$this->segmentA->getId() => 'unsubscribed',
        (string)$this->segmentB->getId() => 'subscribed',
      ],
    ];

    $manage->onSave();

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriber->getFirstName())->equals('John');
    verify($this->createSegmentsMap($subscriber))->equals([
      ['segment_id' => $this->segmentA->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
      ['segment_id' => $this->hiddenSegment->getId(), 'status' => SubscriberEntity::STATUS_SUBSCRIBED],
    ]);
    verify($redirectParams)->equals(['error' => true]);
  }

  public function testItRedirectsWithErrorWhenSubscriberLookupFails(): void {
    $redirectParams = null;
    $manage = $this->getManageService([
      'urlHelper' => Stub::make(UrlHelper::class, [
        'redirectBack' => function($params = []) use (&$redirectParams) {
          $redirectParams = $params;
        },
      ]),
    ]);
    $_POST['action'] = 'mailpoet_subscription_update';
    $_POST['token'] = 'token';
    $_POST['data'] = [
      'first_name' => 'Unknown',
      'last_name' => 'Subscriber',
      'email' => 'unknown@example.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segment_choices' => [
        (string)$this->segmentB->getId() => 'subscribed',
      ],
    ];

    $manage->onSave();

    verify($redirectParams)->equals(['error' => true]);
    verify($this->subscribersRepository->findOneBy(['email' => 'unknown@example.com']))->null();
  }

  private function getManageService(array $overrides = []): Manage {
    return $this->getServiceWithOverrides(Manage::class, array_merge([
      'urlHelper' => Stub::make(UrlHelper::class, [
        'redirectBack' => null,
      ]),
      'fieldNameObfuscator' => Stub::make(FieldNameObfuscator::class, [
        'deobfuscateFormPayload' => function($data) {
          return $data;
        },
      ]),
      'linkTokens' => Stub::make(LinkTokens::class, [
        'verifyToken' => function($token) {
          return true;
        },
      ]),
    ], $overrides));
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
}
