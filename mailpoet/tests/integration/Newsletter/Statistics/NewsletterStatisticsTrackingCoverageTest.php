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

class NewsletterStatisticsTrackingCoverageTest extends \MailPoetTest {
  /** @var NewsletterStatisticsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(NewsletterStatisticsRepository::class);
  }

  public function testItTakesRecipientsSentWithoutTrackingOutOfTheDenominator() {
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

  public function testConsentChangedAfterTheSendDoesNotMoveTheCount() {
    $newsletter = $this->createSentNewsletter(2);
    $optedOutLater = $this->createRecipient($newsletter, true, SubscriberEntity::TRACKING_CONSENT_GRANTED);
    (new StatisticsOpens($newsletter, $optedOutLater))->create();
    $allowedLater = $this->createRecipient($newsletter, false, SubscriberEntity::TRACKING_CONSENT_DENIED);

    $optedOutLater->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);
    $allowedLater->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->entityManager->flush();
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(1);
    verify(($statistics->getOpenCount() * 100) / $statistics->getTrackedSentCount())->equals(100.0);
  }

  public function testChangingSubscriberChoiceDoesNotMoveTheCount() {
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipient($newsletter, true, SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    $this->createRecipient($newsletter, false, SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    $settings = $this->diContainer->get(SettingsController::class);

    foreach (TrackingConsentController::CHOICES as $choice) {
      $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, $choice);
      verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(1);
    }
  }

  public function testADeletedRecipientKeepsTheirPlaceInTheCount() {
    $newsletter = $this->createSentNewsletter(3);
    $subscribers = $this->createRecipients($newsletter, [true, true, false]);

    $this->diContainer->get(SubscribersRepository::class)->bulkDelete([(int)$subscribers[2]->getId()]);
    $newsletter = $this->reloadNewsletter($newsletter);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(1);
    verify($statistics->getTrackedSentCount())->equals(2);
  }

  public function testWithEveryoneTrackedTheDenominatorIsTheFullSentCount() {
    $newsletter = $this->createSentNewsletter(5);
    $this->createRecipients($newsletter, [true, true, true, true, true]);

    $statistics = $this->repository->getStatistics($newsletter);

    verify($statistics->getNotTrackedCount())->equals(0);
    verify($statistics->getTrackedSentCount())->equals(5);
    verify($statistics->getTrackingCoverage())->equals(100.0);
  }

  /**
   * count_processed and the sent rows have different writers (a failed send
   * writes a row without bumping count_processed), so they can drift.
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

  public function testACampaignCountsUntrackedOverTheQueuesItsSentCountSums() {
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2, 'created_at' => new \DateTimeImmutable('-40 days')])
      ->withSendingQueue(['count_processed' => 2, 'count_total' => 2])
      ->create();
    $queues = $newsletter->getQueues()->toArray();
    verify(count($queues))->equals(2);
    foreach ($queues as $queue) {
      $this->createRowInQueue($newsletter, $queue, false);
      $this->createRowInQueue($newsletter, $queue, true);
    }

    $recent = $this->repository->getBatchStatistics([$newsletter], new \DateTimeImmutable('-7 days'), null)[$newsletter->getId()];
    verify($recent->getTotalSentCount())->equals(2);
    verify($recent->getNotTrackedCount())->equals(1);

    $all = $this->repository->getBatchStatistics([$newsletter])[$newsletter->getId()];
    verify($all->getTotalSentCount())->equals(4);
    verify($all->getNotTrackedCount())->equals(2);
  }

  public function testACampaignIgnoresRowsOfAQueueThatHasNotFinished() {
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 1, 'count_total' => 2, 'status' => 'scheduled'])
      ->create();
    $this->createRecipients($newsletter, [false]);

    verify($this->repository->getStatistics($newsletter)->getNotTrackedCount())->equals(0);
  }

  public function testTheBatchAndSingleReadsAgree() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, false, false]);

    $single = $this->repository->getStatistics($newsletter);
    $batch = $this->repository->getBatchStatistics([$newsletter])[$newsletter->getId()];

    verify($batch->getNotTrackedCount())->equals(2);
    verify($batch->getNotTrackedCount())->equals($single->getNotTrackedCount());
    verify($batch->getTrackedSentCount())->equals($single->getTrackedSentCount());
  }

  public function testItExposesTheNewKeysInsideAsArray() {
    $newsletter = $this->createSentNewsletter(4);
    $this->createRecipients($newsletter, [true, true, true, false]);

    $array = $this->repository->getStatistics($newsletter)->asArray();

    verify($array['notTracked'])->equals(1);
    verify($array['trackedSent'])->equals(3);
    verify(round($array['trackingCoverage'], 1))->equals(75.0);
  }

  /**
   * Repeatedly sent emails take their sent count from the sent rows, not from
   * completed queues. This queue never completed, so a queue-based count would
   * see nothing while the total still counts both rows.
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
  }

  public function testARepeatedlySentEmailWindowsUntrackedOnSentAtLikeItsSentCount() {
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withSendingQueue(['count_processed' => 3, 'count_total' => 3, 'created_at' => new \DateTimeImmutable('-40 days')])
      ->create();
    $this->createRecipients($newsletter, [true, false]);
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))
      ->withSentWithTracking(false)
      ->withSentAt(new \DateTimeImmutable('-30 days'))
      ->create();

    $statistics = $this->repository->getBatchStatistics([$newsletter], new \DateTimeImmutable('-7 days'), null)[$newsletter->getId()];

    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(1);
  }

  /**
   * New code can run before the migration adds the column. Stats then show
   * today's numbers instead of failing, and wpdb prints nothing into the response.
   */
  public function testItFallsBackToEveryoneTrackedWhenTheColumnIsMissing() {
    $newsletter = $this->createSentNewsletter(2);
    $this->createRecipients($newsletter, [true, false]);
    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->executeStatement("ALTER TABLE `{$table}` DROP INDEX `newsletter_id_sent_with_tracking`, DROP COLUMN `sent_with_tracking`");

    try {
      ob_start();
      $statistics = $this->repository->getStatistics($newsletter);
      $output = ob_get_clean();
    } finally {
      $connection->executeStatement(
        "ALTER TABLE `{$table}` ADD COLUMN `sent_with_tracking` tinyint(1) NOT NULL DEFAULT 1, ADD INDEX `newsletter_id_sent_with_tracking` (`newsletter_id`, `sent_with_tracking`)"
      );
    }

    verify($output)->equals('');
    verify($statistics->getTotalSentCount())->equals(2);
    verify($statistics->getNotTrackedCount())->equals(0);
  }

  private function createSentNewsletter(int $countProcessed): NewsletterEntity {
    return (new Newsletter())
      ->withSendingQueue(['count_processed' => $countProcessed, 'count_total' => $countProcessed])
      ->create();
  }

  /**
   * @param bool[] $sentWithTracking
   * @return SubscriberEntity[]
   */
  private function createRecipients(NewsletterEntity $newsletter, array $sentWithTracking): array {
    return array_map(
      fn(bool $tracked) => $this->createRecipient($newsletter, $tracked, SubscriberEntity::TRACKING_CONSENT_GRANTED),
      $sentWithTracking
    );
  }

  private function createRecipient(NewsletterEntity $newsletter, bool $sentWithTracking, string $consent): SubscriberEntity {
    $subscriber = (new Subscriber())->withTrackingConsent($consent)->create();
    (new StatisticsNewsletters($newsletter, $subscriber))->withSentWithTracking($sentWithTracking)->create();
    return $subscriber;
  }

  private function createRowInQueue(NewsletterEntity $newsletter, SendingQueueEntity $queue, bool $sentWithTracking): void {
    (new StatisticsNewsletters($newsletter, (new Subscriber())->create()))
      ->withQueue($queue)
      ->withSentWithTracking($sentWithTracking)
      ->create();
  }

  private function reloadNewsletter(NewsletterEntity $newsletter): NewsletterEntity {
    $this->entityManager->clear();
    $reloaded = $this->entityManager->find(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $reloaded);
    return $reloaded;
  }
}
