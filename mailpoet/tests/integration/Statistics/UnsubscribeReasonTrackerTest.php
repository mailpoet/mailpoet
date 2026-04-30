<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Statistics\UnsubscribeReasonTracker;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class UnsubscribeReasonTrackerTest extends \MailPoetTest {
  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  /** @var UnsubscribeReasonTracker */
  private $unsubscribeReasonTracker;

  /** @var \MailPoet\Entities\SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var int */
  private $queueId;

  public function _before() {
    parent::_before();
    $this->statisticsUnsubscribesRepository = $this->diContainer->get(StatisticsUnsubscribesRepository::class);
    $this->unsubscribeReasonTracker = $this->diContainer->get(UnsubscribeReasonTracker::class);
    $this->subscriber = (new SubscriberFactory())->create();
    $this->newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $queue = $this->newsletter->getLatestQueue();
    $this->assertNotNull($queue);
    $this->queueId = (int)$queue->getId();

    $this->diContainer->get(Unsubscribes::class)->track(
      (int)$this->subscriber->getId(),
      StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
      $this->queueId
    );
  }

  public function testItStoresValidatedReasonData(): void {
    $reasonText = '<strong>' . str_repeat('a', UnsubscribeReasonTracker::MAX_REASON_TEXT_LENGTH + 10) . '</strong>';

    $unsubscribe = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $this->queueId,
      StatisticsUnsubscribeEntity::REASON_OTHER,
      $reasonText,
      true
    );

    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $unsubscribe);
    $this->statisticsUnsubscribesRepository->refresh($unsubscribe);
    verify($unsubscribe->getReason())->equals(StatisticsUnsubscribeEntity::REASON_OTHER);
    verify(strlen((string)$unsubscribe->getReasonText()))->equals(UnsubscribeReasonTracker::MAX_REASON_TEXT_LENGTH);
    verify($unsubscribe->getReasonSubmittedAt())->notNull();
  }

  public function testItReturnsSubscriberFacingReasonLabels(): void {
    $reasonLabels = $this->unsubscribeReasonTracker->getReasonLabels();

    verify(array_keys($reasonLabels))->equals([
      StatisticsUnsubscribeEntity::REASON_NO_LONGER_INTERESTED,
      StatisticsUnsubscribeEntity::REASON_DID_NOT_SIGN_UP,
      StatisticsUnsubscribeEntity::REASON_INAPPROPRIATE_CONTENT,
      StatisticsUnsubscribeEntity::REASON_SPAM,
      StatisticsUnsubscribeEntity::REASON_OTHER,
    ]);
    verify($reasonLabels[StatisticsUnsubscribeEntity::REASON_OTHER])->equals('Other');
  }

  public function testItCountsMissingReasonsAsUnspecified(): void {
    $reasonCounts = $this->statisticsUnsubscribesRepository->getReasonCountsForNewsletter($this->newsletter);

    verify($reasonCounts)->equals([
      [
        'reason' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'count' => '1',
      ],
    ]);
  }

  public function testItRejectsInvalidReasonSlugs(): void {
    $unsubscribe = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $this->queueId,
      'invalid_reason',
      null,
      true
    );

    verify($unsubscribe)->null();
    $storedUnsubscribe = $this->statisticsUnsubscribesRepository->findLatestForSubscriber($this->subscriber);
    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $storedUnsubscribe);
    verify($storedUnsubscribe->getReason())->null();
  }

  public function testItIgnoresOtherTextWhenDisabled(): void {
    $unsubscribe = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $this->queueId,
      StatisticsUnsubscribeEntity::REASON_OTHER,
      'Details',
      false
    );

    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $unsubscribe);
    verify($unsubscribe->getReason())->equals(StatisticsUnsubscribeEntity::REASON_OTHER);
    verify($unsubscribe->getReasonText())->null();
  }

  public function testItDoesNotCreateDuplicateUnsubscribeRowsForTheSameQueue(): void {
    $this->diContainer->get(Unsubscribes::class)->track(
      (int)$this->subscriber->getId(),
      StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
      $this->queueId
    );

    verify($this->statisticsUnsubscribesRepository->findBy(['subscriber' => $this->subscriber]))->arrayCount(1);
  }

  public function testItReturnsNullForManipulatedQueueId(): void {
    $otherSubscriber = (new SubscriberFactory())->create();
    $otherNewsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $otherQueue = $otherNewsletter->getLatestQueue();
    $this->assertNotNull($otherQueue);
    $otherQueueId = (int)$otherQueue->getId();

    $this->diContainer->get(Unsubscribes::class)->track(
      (int)$otherSubscriber->getId(),
      StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
      $otherQueueId
    );

    $unsubscribe = $this->unsubscribeReasonTracker->findTargetUnsubscribe($this->subscriber, $otherQueueId);

    verify($unsubscribe)->null();
  }

  public function testItReturnsNullWhenUnsubscribeRecordDoesNotExist(): void {
    $subscriber = (new SubscriberFactory())->create();

    $result = $this->unsubscribeReasonTracker->saveReason(
      $subscriber,
      $this->queueId,
      StatisticsUnsubscribeEntity::REASON_OTHER,
      'Some reason',
      true
    );

    verify($result)->null();
  }

  public function testItConvertsEmptyReasonTextToNull(): void {
    $unsubscribe = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $this->queueId,
      StatisticsUnsubscribeEntity::REASON_OTHER,
      '   ',
      true
    );

    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $unsubscribe);
    verify($unsubscribe->getReason())->equals(StatisticsUnsubscribeEntity::REASON_OTHER);
    verify($unsubscribe->getReasonText())->null();
  }

  public function testItConvertsNullReasonTextToNull(): void {
    $unsubscribe = $this->unsubscribeReasonTracker->saveReason(
      $this->subscriber,
      $this->queueId,
      StatisticsUnsubscribeEntity::REASON_OTHER,
      null,
      true
    );

    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $unsubscribe);
    verify($unsubscribe->getReason())->equals(StatisticsUnsubscribeEntity::REASON_OTHER);
    verify($unsubscribe->getReasonText())->null();
  }
}
