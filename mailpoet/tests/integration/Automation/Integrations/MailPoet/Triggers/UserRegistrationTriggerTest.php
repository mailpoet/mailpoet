<?php

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\SubscribersRepository;

class UserRegistrationTriggerTest extends \MailPoetTest
{

  const USER_NAME = 'user-name--x';
  const USER_EMAIL = 'user-name--x@mailpoet.com';
  const USER_ROLE = 'subscriber';

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ?int */
  private $userId = null;

  /** @var WP */
  private $wpSegment;

  public function _before() {

    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->segmentRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    if (! is_numeric($this->userId)) {
      $userId = wp_insert_user([
        'user_login' => self::USER_NAME,
        'user_pass' => 'abc',
        'user_email' => self::USER_EMAIL,
        'role' => self::USER_ROLE,
      ]);
      assert(is_numeric($userId));
      $this->userId = $userId;
      $this->wpSegment->synchronizeUsers();
    }
  }

  /**
   * @dataProvider dataForTestTriggeredByWorkflowRun
   * @param array $roleSetting
   * @param bool $expectation
   */
  public function testTriggeredByWorkflowRun(array $roleSetting, bool $expectation) {
    /** @var UserRegistrationTrigger $testee */
    $testee = $this->diContainer->get(UserRegistrationTrigger::class);

    $args = [
      'roles' => $roleSetting,
    ];

    $subscriber = $this->subscribersRepository->findOneBy(['email' => self::USER_EMAIL]);
    assert($subscriber instanceof SubscriberEntity);

    /** @var SubscriberSubject $subscriberSubject */
    $subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    $subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);
    $segment = $this->segmentRepository->getWPUsersSegment();
    assert($segment instanceof SegmentEntity);
    /** @var SegmentSubject $subscriberSubject */
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $segment->getId()]);
    $this->assertSame($expectation, $testee->isTriggeredBy($args, $subscriberSubject, $segmentSubject));
  }

  public function dataForTestTriggeredByWorkflowRun() : array {
    return [
      'any_role' => [
        [], //Any list setting
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

  public function _after() {
    if (! $this->userId) {
      return;
    }
    is_multisite() ? wpmu_delete_user($this->userId) : wp_delete_user($this->userId);
    $this->userId = null;
    $this->wpSegment->synchronizeUsers();
  }
}
