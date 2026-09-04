<?php declare(strict_types = 1);

namespace integration\Newsletter\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * Tracked-only denominator, computed at read time from each recipient's
 * consent and WHEN it changed. Every assertion pins an exact integer.
 */
class NewsletterStatisticsTrackingCoverageTest extends \MailPoetTest {
  /** @var NewsletterStatisticsRepository */
  private $repository;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewsletterStatisticsRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_NEW);
    $this->settings->set(TrackingConsentController::SETTING_STRICT_SINCE, '');
  }

  /** Four recipients, one opted out before the send, two opens: 2/3, not 2/4. */
  public function testItTakesRecipientsWhoOptedOutBeforeTheSendOutOfTheDenominator() {
    $newsletter = $this->createSentNewsletter(4);
    $subscribers = $this->createRecipients($newsletter, [true, true, true, false]);
    (new StatisticsOpens($newsletter, $subscribers[0]))->create();
    (new StatisticsOpens($newsletter, $subscribers[1]))->create();

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(4);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(3);
    verify(round($statistics->getTrackingCoverage(), 1))->equals(75.0);
    verify(round(($statistics->getOpenCount() * 100) / $statistics->getTrackedSentCount(), 1))->equals(66.7);
  }

  /**
   * The case the read-time design has to get right: someone tracked at send,
   * who opened, and who opted out AFTERWARDS. Their open stays in the numerator,
   * so they must stay in the denominator — otherwise the rate can exceed 100%.
   * The stats row is dated yesterday on purpose: sent_at and the consent
   * timestamp are both second-precision, and a same-second change would satisfy
   * "<=" and hide the bug this test guards.
   */
  public function testAnOptOutAfterTheSendDoesNotChangeThatSend() {
    $newsletter = $this->createSentNewsletter(1);
    $subscriber = (new Subscriber())->create();
    (new StatisticsNewsletters($newsletter, $subscriber))
      ->withSentAt(new \DateTimeImmutable('-1 day'))
      ->create();
    (new StatisticsOpens($newsletter, $subscriber))->create();

    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);
    $this->entityManager->flush();
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(1);
    verify(($statistics->getOpenCount() * 100) / $statistics->getTrackedSentCount())->equals(100.0);
  }

  /** Denied before the send: excluded. Same recipient, only the date differs from the test above. */
  public function testAnOptOutBeforeTheSendIsExcluded() {
    $newsletter = $this->createSentNewsletter(1);
    $subscriber = (new Subscriber())
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-1 day'))
      ->create();
    (new StatisticsNewsletters($newsletter, $subscriber))->create();

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(0);
  }

  /**
   * bulkDelete() does not clean statistics_newsletters, so a deleted recipient
   * leaves an orphan row. The inner join drops it: it is not counted as
   * untracked and stays in the denominator, exactly as today.
   */
  public function testADeletedRecipientIsNotCountedAsUntracked() {
    $newsletter = $this->createSentNewsletter(3);
    $subscribers = $this->createRecipients($newsletter, [true, false, false]);

    $this->diContainer->get(SubscribersRepository::class)->bulkDelete([(int)$subscribers[2]->getId()]);
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(3);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(2);
  }

  /** In strict mode (ask_all) people we never asked are not tracked either. */
  public function testStrictModeAlsoExcludesRecipientsWhoWereNeverAsked() {
    $newsletter = $this->createSentNewsletter(3);
    $this->createRecipients($newsletter, [true, false]);
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))->create(); // unknown

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1);

    $this->switchToStrictMode(new \DateTimeImmutable('-2 days'));
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);
    verify($statistics->getNotTrackedCount())->equals(2);
    verify($statistics->getTrackedSentCount())->equals(1);
  }

  /**
   * Seun's live case, 2026-08-19. A campaign went out while the site tracked
   * never-asked recipients; three of them opened and clicked, and those events
   * are on record. Switching to "ask everyone" afterwards must not reach back
   * and re-label them, or the denominator collapses under a numerator that
   * still counts their opens — on his site that read 300%.
   */
  public function testTurningOnStrictModeDoesNotChangeACampaignSentBeforeIt() {
    $sentAt = new \DateTimeImmutable('-6 days');
    $newsletter = $this->createSentNewsletter(5);

    // One granted and two who opted out before the send.
    $granted = (new Subscriber())->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED)->create();
    (new StatisticsNewsletters($newsletter, $granted))->withSentAt($sentAt)->create();
    for ($i = 0; $i < 2; $i++) {
      $denied = (new Subscriber())
        ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-7 days'))
        ->create();
      (new StatisticsNewsletters($newsletter, $denied))->withSentAt($sentAt)->create();
    }

    // Two never asked. They were tracked when this went out, and it shows:
    // their opens are on record.
    foreach ([0, 1] as $ignored) {
      $subscriber = (new Subscriber())->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($sentAt)->create();
      (new StatisticsOpens($newsletter, $subscriber))->create();
    }
    (new StatisticsOpens($newsletter, $granted))->create();

    $before = $this->repository->getStatistics($newsletter);
    verify($before->getNotTrackedCount())->equals(2);
    verify($before->getTrackedSentCount())->equals(3);
    verify($before->getOpenCount())->equals(3);

    // Strict mode starts now, i.e. after this campaign was sent.
    $this->switchToStrictMode(new \DateTimeImmutable());
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $after = $this->repository->getStatistics($newsletter);
    verify($after->getNotTrackedCount())->equals(2);
    verify($after->getTrackedSentCount())->equals(3);
    verify(($after->getOpenCount() * 100) / $after->getTrackedSentCount())->equals(100.0);
  }

  /** Sends made after the switch do exclude never-asked recipients. */
  public function testStrictModeAppliesToSendsMadeAfterItWasTurnedOn() {
    $this->switchToStrictMode(new \DateTimeImmutable('-1 day'));
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true]);
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))->create(); // never asked

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);
  }

  /**
   * A site asking everyone with no record of when it started leaves every
   * number alone. Safer than guessing a date and moving history.
   */
  public function testStrictModeWithoutARecordedStartDateChangesNothing() {
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true]);
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))->create(); // never asked

    $this->settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);
    $this->settings->set(TrackingConsentController::SETTING_STRICT_SINCE, '');
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(2);
  }

  /** Acceptance criterion 3, as exact equality. */
  public function testWithNoOptOutsTheTrackedDenominatorIsTheFullSentCount() {
    $newsletter = $this->createSentNewsletter(5);
    $this->createRecipients($newsletter, [true, true, true, true, true]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(5);
    verify($statistics->getTrackingCoverage())->equals(100.0);
  }

  /**
   * count_processed and the statistics_newsletters rows have different writers
   * (a failed send writes a row without bumping count_processed), so they can
   * drift. The untracked count is bounded to the audience it describes.
   */
  public function testTheUntrackedCountIsBoundedToTheSentCountWhenTheCountersDrift() {
    $newsletter = $this->createSentNewsletter(1);
    $this->createRecipients($newsletter, [false, false, false]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(1);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(0);
    verify($statistics->getTrackingCoverage())->equals(0.0);
  }

  /** The window must select the same queues the sent count sums. */
  public function testTheUntrackedCountRespectsTheSameTimeWindowAsTheSentCount() {
    $old = new \DateTimeImmutable('-40 days');
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2, 'created_at' => $old])
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2])
      ->create();
    $queues = $newsletter->getQueues()->toArray();
    verify(count($queues))->equals(2);
    /** @var SendingQueueEntity $oldQueue */
    $oldQueue = $queues[0];
    /** @var SendingQueueEntity $recentQueue */
    $recentQueue = $queues[1];

    foreach ([$oldQueue, $recentQueue] as $queue) {
      $denied = (new Subscriber())
        ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-1 day'))
        ->create();
      $this->createRowInQueue($newsletter, $queue, $denied);
      $this->createRowInQueue($newsletter, $queue, (new Subscriber())->create());
    }

    $batch = $this->repository->getBatchStatistics([$newsletter], new \DateTimeImmutable('-7 days'), null);
    $statistics = $batch[$newsletter->getId()];
    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);

    $all = $this->repository->getBatchStatistics([$newsletter])[$newsletter->getId()];
    verify($all->getTotalSentCount())->equals(4);
    verify($all->getNotTrackedCount())->equals(2);
    verify($all->getTrackedSentCount())->equals(2);
  }

  /** Listing (batch) and campaign stats (single) must agree. */
  public function testTheBatchAndSingleReadsAgree() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, false, false]);

    $single = $this->repository->getStatistics($newsletter);
    $batch = $this->repository->getBatchStatistics([$newsletter])[$newsletter->getId()];

    verify($batch->getNotTrackedCount())->equals(2);
    verify($batch->getNotTrackedCount())->equals($single->getNotTrackedCount());
    verify($batch->getTrackedSentCount())->equals($single->getTrackedSentCount());
  }

  /** Inside asArray(): Premium re-emits this array verbatim, so this is what keeps the change free-plugin-only. */
  public function testItExposesTheNewKeysInsideAsArray() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, true, false]);

    $array = $this->repository->getStatistics($newsletter)->asArray();

    verify($array['notTracked'])->equals(1);
    verify($array['trackedSent'])->equals(3);
    verify(round($array['trackingCoverage'], 1))->equals(75.0);
  }

  /**
   * Repeatedly sent emails (welcome, automation, re-engagement) get their sent
   * count from the sending statistics rows, not from completed queues — see
   * getRecordedSentCounts(). The untracked count has to be read over those very
   * rows, or the two sides of the subtraction describe different audiences.
   *
   * This fixture has a queue whose task never completed, so a queue-based count
   * sees nothing while the total still counts both rows.
   */
  public function testARepeatedlySentEmailCountsUntrackedOverTheSameRowsAsItsSentCount() {
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withScheduledQueue(['count_processed' => 0, 'count_total' => 2])
      ->create();
    $this->createRecipients($newsletter, [true, false]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);
    verify($statistics->getTrackingCoverage())->equals(50.0);
  }

  /**
   * Same split, but through the window. The recorded total filters on
   * stats.sentAt, so the untracked count must use that column too — filtering on
   * the queue's created_at instead would count a different set of rows.
   */
  public function testARepeatedlySentEmailWindowsUntrackedOnSentAtLikeItsSentCount() {
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2, 'created_at' => new \DateTimeImmutable('-40 days')])
      ->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    // Both sent recently, on a queue created well outside the window.
    $denied = (new Subscriber())
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-1 day'))
      ->create();
    $granted = (new Subscriber())
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED)
      ->create();
    (new StatisticsNewsletters($newsletter, $denied))->create();
    (new StatisticsNewsletters($newsletter, $granted))->create();

    $statistics = $this->repository->getBatchStatistics([$newsletter], new \DateTimeImmutable('-7 days'), null)[$newsletter->getId()];

    // The total counts both rows because their sent_at is inside the window.
    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);
  }

  /**
   * The documented limit of reading consent at display time, pinned so nobody
   * "fixes" it by accident.
   *
   * Someone denied before a send, who later allows tracking, looks `granted`
   * now, and the row carries only their LAST change — there is no history to
   * say what they were on the day. So they come back into the denominator for
   * that old campaign even though no open could ever have been recorded for
   * them, and the rate reads slightly low.
   *
   * Accepted on purpose. The error is always in this direction — it can only
   * put someone back in, never take an engaged recipient out — so it can never
   * push a rate over 100%, and it lands on the number trunk shows today. The
   * alternative is storing eligibility per recipient at send time, which is the
   * column-on-the-largest-table design this PR replaced.
   */
  public function testARecipientWhoAllowsTrackingAfterASendReturnsToThatSendsDenominator() {
    $newsletter = $this->createSentNewsletter(2);
    $subscriber = (new Subscriber())
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-2 days'))
      ->create();
    (new StatisticsNewsletters($newsletter, $subscriber))->create();
    $this->createRecipients($newsletter, [true]);

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1);

    // They change their mind after the campaign went out.
    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->entityManager->flush();
    $this->clearCachedCounts();
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);
    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(2);
  }

  /** Same limit on the strict-mode side: never asked at the send, allowed afterwards. */
  public function testANeverAskedRecipientWhoAllowsTrackingAfterAStrictSendReturnsToTheDenominator() {
    $this->switchToStrictMode(new \DateTimeImmutable('-1 day'));
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true]);
    $neverAsked = (new Subscriber())->create();
    (new StatisticsNewsletters($newsletter, $neverAsked))->create();

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1);

    $neverAsked->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->entityManager->flush();
    $this->clearCachedCounts();
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(0);
  }

  // ---- cache -------------------------------------------------------------

  /** First read stores the number on the queue, keyed by the strict-mode flag it was computed under. */
  public function testTheFirstReadCachesTheCountOnTheCompletedQueue() {
    $newsletter = $this->createSentNewsletter(3);
    $this->createRecipients($newsletter, [true, false, false]);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($queue->getMeta()[NewsletterStatisticsRepository::META_NOT_TRACKED] ?? null)->null();

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(2);

    $meta = $this->readQueueMetaFromDb((int)$queue->getId());
    verify($meta[NewsletterStatisticsRepository::META_NOT_TRACKED])->equals(['count' => 2, 'unknownUntrackedSince' => null]);
  }

  /**
   * The cache is what is read once it exists: adding an opted-out recipient row
   * to a completed queue afterwards (a drift/backfill situation) does not move
   * the number. History is frozen at first read.
   */
  public function testACachedCountIsUsedInsteadOfTheLiveQuery() {
    $newsletter = $this->createSentNewsletter(3);
    $this->createRecipients($newsletter, [true, false, false]);
    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(2);

    $this->createRecipients($newsletter, [false]); // live query would now say 3
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(2);
  }

  /** A value cached under one Subscriber-choice mode is not reused under the other. */
  public function testACachedCountFromAnotherModeIsRecomputed() {
    $newsletter = $this->createSentNewsletter(3);
    $this->createRecipients($newsletter, [true, false]);
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))->create(); // unknown
    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1); // ask_new, cached

    $strictSince = new \DateTimeImmutable('-2 days');
    $this->switchToStrictMode($strictSince);
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);
    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(2); // recomputed

    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $meta = $this->readQueueMetaFromDb((int)$queue->getId());
    verify($meta[NewsletterStatisticsRepository::META_NOT_TRACKED])->equals([
      'count' => 2,
      'unknownUntrackedSince' => $strictSince->format('Y-m-d H:i:s'),
    ]);
  }

  /** Other keys on the queue's meta survive the write (the clobber guard). */
  public function testCachingKeepsTheOtherMetaKeys() {
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true, false]);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setMeta(['campaignId' => 'abc123', 'filterSegment' => ['id' => 7]]);
    $this->entityManager->flush();

    $this->repository->getStatistics($newsletter);

    $meta = $this->readQueueMetaFromDb((int)$queue->getId());
    verify($meta['campaignId'])->equals('abc123');
    verify($meta['filterSegment'])->equals(['id' => 7]);
    verify($meta[NewsletterStatisticsRepository::META_NOT_TRACKED]['count'])->equals(1);
  }

  /** A queue whose task is still running is neither counted nor cached. */
  public function testAnUnfinishedQueueIsNotCached() {
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 1, 'count_total' => 2, 'status' => ScheduledTaskEntity::STATUS_SCHEDULED])
      ->create();
    $this->createRecipients($newsletter, [false]);

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(0);

    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    verify($this->readQueueMetaFromDb((int)$queue->getId())[NewsletterStatisticsRepository::META_NOT_TRACKED] ?? null)->null();
  }

  /** The cache write must not bump the queue's updated_at (it is shown in the listing). */
  public function testCachingDoesNotTouchTheQueueUpdatedAt() {
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true, false]);
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $before = $this->entityManager->getConnection()->fetchOne(
      'SELECT updated_at FROM ' . $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName() . ' WHERE id = ?',
      [$queue->getId()]
    );

    sleep(1);
    $this->repository->getStatistics($newsletter);

    $after = $this->entityManager->getConnection()->fetchOne(
      'SELECT updated_at FROM ' . $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName() . ' WHERE id = ?',
      [$queue->getId()]
    );
    verify($after)->equals($before);
  }

  // ---- helpers -----------------------------------------------------------

  /** Drop every cached count so the next read runs the live query. */
  private function clearCachedCounts(): void {
    $table = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("UPDATE `{$table}` SET meta = NULL, updated_at = updated_at");
  }

  /** Ask everyone, from the given moment. */
  private function switchToStrictMode(\DateTimeImmutable $since): void {
    $this->settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);
    $this->settings->set(TrackingConsentController::SETTING_STRICT_SINCE, $since->format('Y-m-d H:i:s'));
  }

  private function readQueueMetaFromDb(int $queueId): array {
    $table = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $json = $this->entityManager->getConnection()->fetchOne("SELECT meta FROM `{$table}` WHERE id = ?", [$queueId]);
    return is_string($json) ? (array)json_decode($json, true) : [];
  }

  private function createSentNewsletter(int $countProcessed): NewsletterEntity {
    return (new Newsletter())
      ->withSendingQueue(['count_processed' => $countProcessed, 'count_total' => $countProcessed])
      ->create();
  }

  /**
   * true = granted, false = opted out yesterday, i.e. before the row's sent_at
   * (now). Granted is set explicitly rather than left at the entity default,
   * which is 'unknown' — and 'unknown' is untracked in strict mode, which would
   * make the strict-mode tests count recipients they do not mean to.
   *
   * @param bool[] $trackedFlags
   * @return SubscriberEntity[]
   */
  private function createRecipients(NewsletterEntity $newsletter, array $trackedFlags): array {
    $subscribers = [];
    foreach ($trackedFlags as $tracked) {
      $factory = new Subscriber();
      if ($tracked) {
        $factory->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
      } else {
        $factory->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED, new \DateTimeImmutable('-1 day'));
      }
      $subscriber = $factory->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->create();
      $subscribers[] = $subscriber;
    }
    return $subscribers;
  }

  private function createRowInQueue(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): void {
    $entity = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($entity);
    $this->entityManager->flush();
  }

  private function reloadNewsletter(NewsletterEntity $newsletter): NewsletterEntity {
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    return $reloaded;
  }
}
