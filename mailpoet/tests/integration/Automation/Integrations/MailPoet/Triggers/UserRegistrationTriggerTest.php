<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions;

class UserRegistrationTriggerTest extends \MailPoetTest {
  const USER_NAME = 'user-name--x';
  const USER_EMAIL = 'user-name--x@mailpoet.com';
  const USER_ROLE = 'subscriber';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var ?int */
  private $userId = null;

  /** @var WP */
  private $wpSegment;

  public function _before() {
    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    if (!is_numeric($this->userId)) {
      $userId = wp_insert_user([
        'user_login' => self::USER_NAME,
        'user_pass' => 'abc',
        'user_email' => self::USER_EMAIL,
        'role' => self::USER_ROLE,
      ]);
      $this->assertIsNumeric($userId);
      $this->userId = $userId;
      $this->wpSegment->synchronizeUsers();
    }
  }

  public function testCanHandleRegistration() {
    $wpMock = $this->createMock(Functions::class);
    $testee = new UserRegistrationTrigger(
      $wpMock,
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(AutomationRunStorage::class)
    );

    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $this->userId]);
    $segment = $this->segmentRepository->getWPUsersSegment();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    $this->subscriberSegmentRepository->subscribeToSegments($subscriber, [$segment]);

    $wpMock->expects($this->once())->method(
      'doAction'
    )->willReturnCallback(function($hook, $trigger, array $subjects) use ($testee, $subscriber) {
      $this->assertSame(Hooks::TRIGGER, $hook);
      $this->assertSame($trigger, $testee);

      /** @var Subject[] $subjects */
      $this->assertSame(SegmentSubject::KEY, $subjects[0]->getKey());
      $this->assertSame(SubscriberSubject::KEY, $subjects[1]->getKey());

      $wpUserSegment = $this->segmentRepository->getWPUsersSegment();
      $this->assertInstanceOf(SegmentEntity::class, $wpUserSegment);
      $this->assertSame($wpUserSegment->getId(), $subjects[0]->getArgs()['segment_id']);
      $this->assertSame($subscriber->getId(), $subjects[1]->getArgs()['subscriber_id']);
    });

    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriber]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $testee->handleSubscription($subscriberSegment);
  }

  /**
   * @dataProvider dataForTestTriggeredByAutomationRun
   */
  public function testTriggeredByAutomationRun(array $roleSetting, bool $expectation) {
    $testee = $this->diContainer->get(UserRegistrationTrigger::class);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => self::USER_EMAIL]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);

    $segment = $this->segmentRepository->getWPUsersSegment();
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    $stepRunArgs = new StepRunArgs(
      $this->make(
        Automation::class,
        [
          'getId' => 1,
        ]
      ),
      $this->make(
        AutomationRun::class,
        [
          'getSubjectHash' => 'hash',
        ]
      ),
      new Step('test-id', 'trigger', 'test:trigger', ['roles' => $roleSetting], []),
      [
        new SubjectEntry(
          $this->diContainer->get(SegmentSubject::class),
          new Subject('mailpoet:segment', ['segment_id' => $segment->getId()])
        ),
        new SubjectEntry(
          $this->diContainer->get(SubscriberSubject::class),
          new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriber->getId()])
        ),
      ]
    );
    $this->assertSame($expectation, $testee->isTriggeredBy($stepRunArgs));
  }

  public function dataForTestTriggeredByAutomationRun(): array {
    return [
      'any_role' => [
        [], // any list
        true,
      ],
      'list_match' => [
        [self::USER_ROLE],
        true,
      ],
      'list_mismatch' => [
        ['editor'],
        false,
      ],
    ];
  }

  /**
   * @dataProvider dataForTestItObeysMultipleRunsSetting
   */
  public function testItObeysMultipleRunsSetting(bool $runMultipleTimes, int $expectedRuns) {
    $automation = $this->tester->createAutomation('test',
      new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        UserRegistrationTrigger::KEY,
        [
          'roles' => [],
          'run_multiple_times' => $runMultipleTimes,
        ],
        []
      ));
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $this->userId]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriber]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);

    /** @var UserRegistrationTrigger $testee */
    $testee = $this->diContainer->get(UserRegistrationTrigger::class);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $testee->handleSubscription($subscriberSegment);
    $this->assertCount(1, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
    $testee->handleSubscription($subscriberSegment);
    $this->assertCount($expectedRuns, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function dataForTestItObeysMultipleRunsSetting(): array {
    return [
      'run_only_once' => [false, 1],
      'run_more_often' => [true, 2],
    ];
  }

  public function _after() {
    if (!$this->userId) {
      return;
    }
    is_multisite() ? wpmu_delete_user($this->userId) : wp_delete_user($this->userId);
    $this->userId = null;
    $this->wpSegment->synchronizeUsers();
    $this->subscriberSegmentRepository->truncate();
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
  }
}
