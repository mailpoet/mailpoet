<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class LatestNewsletterReplayStatisticsTest extends \MailPoetTest {
  private NewsletterStatisticsRepository $statisticsRepository;

  public function _before() {
    parent::_before();
    $this->statisticsRepository = $this->diContainer->get(NewsletterStatisticsRepository::class);
  }

  public function testItExcludesReplayQueuesFromCampaignTotalSentCount(): void {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'count_processed' => 5,
        'count_total' => 5,
        'processed_at' => Carbon::parse('2026-01-01 10:00:00'),
      ])
      ->withSendingQueue([
        'count_processed' => 1,
        'count_total' => 1,
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
        'meta' => [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true],
      ])
      ->create();

    $statistics = $this->statisticsRepository->getStatistics($newsletter);

    $this->assertSame(5, $statistics->getTotalSentCount());
  }

  public function testItExcludesReplayQueuesFromCampaignOpenAndClickCounts(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
        'meta' => [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true],
      ])
      ->create();

    $link = (new NewsletterLink($newsletter))->create();
    (new StatisticsOpens($newsletter, $subscriber))->create();
    (new StatisticsClicks($link, $subscriber))->create();

    $statistics = $this->statisticsRepository->getStatistics($newsletter);

    $this->assertSame(0, $statistics->getOpenCount());
    $this->assertSame(0, $statistics->getClickCount());
  }
}
