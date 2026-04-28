<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics;

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

  /** @var int */
  private $queueId;

  public function _before() {
    parent::_before();
    $this->statisticsUnsubscribesRepository = $this->diContainer->get(StatisticsUnsubscribesRepository::class);
    $this->unsubscribeReasonTracker = $this->diContainer->get(UnsubscribeReasonTracker::class);
    $this->subscriber = (new SubscriberFactory())->create();
    $newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $queue = $newsletter->getLatestQueue();
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
}
