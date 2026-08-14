<?php declare(strict_types = 1);

namespace integration\Newsletter\Statistics;

use MailPoet\Entities\NewsletterEntity;
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
 * Tracking coverage: how many of a campaign's recipients we were allowed to
 * measure, and the tracked-only denominator that open and click rates use.
 *
 * Every assertion here pins an exact integer. A test asserting only that
 * notTracked >= 0, or that a coverage figure exists, would pass whether or not
 * the denominator actually changed.
 */
class NewsletterStatisticsTrackingCoverageTest extends \MailPoetTest {
  /** @var NewsletterStatisticsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewsletterStatisticsRepository::class);
  }

  /**
   * The headline case from the issue. Four recipients, one opted out at send
   * time, two opens. The honest rate is 2/3, not 2/4.
   */
  public function testItTakesUntrackedRecipientsOutOfTheOpenRateDenominator() {
    $newsletter = $this->createSentNewsletter(4);
    $subscribers = $this->createRecipients($newsletter, [true, true, true, false]);
    (new StatisticsOpens($newsletter, $subscribers[0]))->create();
    (new StatisticsOpens($newsletter, $subscribers[1]))->create();

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(4);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(3);

    // 66.7%, not 50%. This is the whole point of the change.
    $openRate = ($statistics->getOpenCount() * 100) / $statistics->getTrackedSentCount();
    verify(round($openRate, 1))->equals(66.7);
    verify(round(($statistics->getOpenCount() * 100) / $statistics->getTotalSentCount(), 1))->equals(50.0);
  }

  /**
   * The snapshot guard, and the one test that makes Option B (store the
   * decision at send time) distinguishable from Option A (read current consent
   * at display time). Without it, a later refactor can quietly turn one into
   * the other and nothing fails.
   */
  public function testTheUntrackedCountDoesNotMoveWhenTheSubscriberChoiceSettingChanges() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_NEW);

    $newsletter = $this->createSentNewsletter(3);
    // One denied, two never asked. Under ask_new the unknown pair counted as
    // tracked at send time, so that is what the campaign records forever.
    $subscribers = $this->createRecipients($newsletter, [true, true, false]);
    $subscribers[0]->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    $subscribers[1]->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    $subscribers[2]->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);
    $this->entityManager->flush();

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1);

    foreach (TrackingConsentController::CHOICES as $choice) {
      $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, $choice);
      $this->entityManager->clear();
      $newsletter = $this->reloadNewsletter($newsletter);
      $statistics = $this->repository->getStatistics($newsletter);
      verify($statistics->getNotTrackedCount())->equals(1);
      verify($statistics->getTrackedSentCount())->equals(2);
    }
  }

  /**
   * Someone tracked at send, who opened, and who opted out afterwards. Their
   * open stays in the numerator, so they must stay in the denominator too —
   * otherwise the rate can exceed 100%.
   */
  public function testAnOptOutAfterTheSendCannotPushTheRateOverOneHundred() {
    $newsletter = $this->createSentNewsletter(1);
    $subscribers = $this->createRecipients($newsletter, [true]);
    (new StatisticsOpens($newsletter, $subscribers[0]))->create();

    $subscribers[0]->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);
    $this->entityManager->flush();
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(1);
    verify(($statistics->getOpenCount() * 100) / $statistics->getTrackedSentCount())->equals(100.0);
  }

  /**
   * bulkDelete() cleans segments, custom fields and tags but not
   * statistics_newsletters, so deleting a recipient leaves an orphan row. The
   * snapshot keeps counting it, which is what stops the denominator shrinking
   * out from under a recorded open.
   */
  public function testDeletingARecipientDoesNotChangeTheCoverage() {
    $newsletter = $this->createSentNewsletter(3);
    $subscribers = $this->createRecipients($newsletter, [true, true, false]);

    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscribersRepository->bulkDelete([(int)$subscribers[2]->getId()]);
    $this->entityManager->clear();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(2);
  }

  /**
   * The acceptance criterion "behavior is unchanged for sites with no opted-out
   * subscribers", asserted as exact integer equality rather than "close".
   */
  public function testWithNoOptOutsTheTrackedDenominatorIsTheFullSentCount() {
    $newsletter = $this->createSentNewsletter(5);
    $this->createRecipients($newsletter, [true, true, true, true, true]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals($statistics->getTotalSentCount());
    verify($statistics->getTrackedSentCount())->equals(5);
  }

  /**
   * count_processed and the statistics_newsletters row count come from two
   * different writers — a failed send writes stats rows without bumping
   * count_processed — so they can genuinely drift. The clamp keeps a drifting
   * site from showing a negative denominator.
   */
  public function testTheTrackedCountIsClampedAtZeroWhenTheCountersDrift() {
    $newsletter = $this->createSentNewsletter(1);
    $this->createRecipients($newsletter, [false, false, false]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getTotalSentCount())->equals(1);
    verify($statistics->getNotTrackedCount())->equals(3);
    verify($statistics->getTrackedSentCount())->equals(0);
  }

  /**
   * The window must select the same queues the sent count sums, or subtracting
   * one from the other is meaningless.
   */
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

    // One untracked recipient in each queue.
    foreach ([$oldQueue, $recentQueue] as $queue) {
      (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))
        ->withQueue($queue)->withTrackingAllowed(false)->create();
      (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))
        ->withQueue($queue)->withTrackingAllowed(true)->create();
    }

    $from = new \DateTimeImmutable('-7 days');
    $batch = $this->repository->getBatchStatistics([$newsletter], $from, null);
    $statistics = $batch[$newsletter->getId()];

    // Only the recent queue is in the window, on both sides of the subtraction.
    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);

    $all = $this->repository->getBatchStatistics([$newsletter]);
    verify($all[$newsletter->getId()]->getTotalSentCount())->equals(4);
    verify($all[$newsletter->getId()]->getNotTrackedCount())->equals(2);
    verify($all[$newsletter->getId()]->getTrackedSentCount())->equals(2);
  }

  /**
   * The listing reads batch statistics and the campaign stats page reads single
   * statistics. A merchant seeing two different open rates for one campaign
   * trusts neither, so the two paths must agree.
   */
  public function testTheBatchAndSingleReadsAgree() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, false, false]);

    $single = $this->repository->getStatistics($newsletter);
    $batch = $this->repository->getBatchStatistics([$newsletter])[$newsletter->getId()];

    verify($batch->getNotTrackedCount())->equals($single->getNotTrackedCount());
    verify($batch->getTrackedSentCount())->equals($single->getTrackedSentCount());
    verify($batch->getNotTrackedCount())->equals(2);
    verify($batch->getTrackedSentCount())->equals(2);
  }

  public function testItExposesTheNewKeysInsideAsArray() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, true, false]);

    $array = $this->repository->getStatistics($newsletter)->asArray();

    // Inside asArray(), not alongside it: Premium re-emits this array verbatim,
    // so keeping the new keys here is what makes the change free-plugin-only.
    verify($array['notTracked'])->equals(1);
    verify($array['trackedSent'])->equals(3);
  }

  /**
   * The update window. A site can run this code before the migration that adds
   * tracking_allowed, and these read paths are reachable from ordinary page
   * loads — the automation analytics endpoint returned a 500 with
   * "Unknown column 'tracking_allowed' in 'where clause'".
   *
   * Reproduced by dropping the column, which is the only faithful way to test
   * it: every other suite runs against a fully migrated database, which is
   * exactly why this class of bug survives CI.
   */
  public function testItSurvivesAMissingTrackingAllowedColumn() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, true, false]);

    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->executeStatement("ALTER TABLE `{$table}` DROP COLUMN `tracking_allowed`");

    try {
      $this->entityManager->clear();
      $statistics = $this->repository->getStatistics($this->reloadNewsletter($newsletter));

      // No exception, and rates fall back to the whole audience — the exact
      // behaviour from before this feature, rather than a 500.
      verify($statistics->getTotalSentCount())->equals(4);
      verify($statistics->getNotTrackedCount())->equals(0);
      verify($statistics->getTrackedSentCount())->equals(4);
    } finally {
      $connection->executeStatement(
        "ALTER TABLE `{$table}` ADD COLUMN `tracking_allowed` tinyint(1) NOT NULL DEFAULT 1"
      );
    }
  }

  private function createSentNewsletter(int $countProcessed): NewsletterEntity {
    return (new Newsletter())
      ->withSendingQueue(['count_processed' => $countProcessed, 'count_total' => $countProcessed])
      ->create();
  }

  /**
   * @param bool[] $trackingAllowedFlags
   * @return SubscriberEntity[]
   */
  private function createRecipients(NewsletterEntity $newsletter, array $trackingAllowedFlags): array {
    $subscribers = [];
    foreach ($trackingAllowedFlags as $trackingAllowed) {
      $subscriber = (new Subscriber())->create();
      (new StatisticsNewsletters($newsletter, $subscriber))
        ->withTrackingAllowed($trackingAllowed)
        ->create();
      $subscribers[] = $subscriber;
    }
    return $subscribers;
  }

  private function reloadNewsletter(NewsletterEntity $newsletter): NewsletterEntity {
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    return $reloaded;
  }
}
