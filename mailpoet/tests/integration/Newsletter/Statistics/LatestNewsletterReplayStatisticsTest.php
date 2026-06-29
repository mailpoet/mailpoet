<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class LatestNewsletterReplayStatisticsTest extends \MailPoetTest {
  private NewsletterStatisticsRepository $statisticsRepository;

  public function _before() {
    parent::_before();
    $this->statisticsRepository = $this->diContainer->get(NewsletterStatisticsRepository::class);
  }

  public function testItIncludesReplayQueuesInCampaignTotalSentCount(): void {
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

    $this->assertSame(6, $statistics->getTotalSentCount());
  }

  public function testItIncludesReplayQueuesInCampaignOpenAndClickCounts(): void {
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

    $this->assertSame(1, $statistics->getOpenCount());
    $this->assertSame(1, $statistics->getClickCount());
  }

  /**
   * @group woo
   */
  public function testItIncludesReplayQueuesInCampaignRevenue(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
        'meta' => [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true],
      ])
      ->create();

    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();
    (new StatisticsWooCommercePurchases($click, [
      'id' => 10001,
      'currency' => 'USD',
      'total' => 25,
      'status' => 'completed',
    ]))->create();

    $revenue = $this->statisticsRepository->getWooCommerceRevenue($newsletter);

    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertSame(1, $revenue->getOrdersCount());
    $this->assertSame(25.0, $revenue->getValue());
  }

  public function testItIncludesCampaignStatisticsWithoutQueue(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
      ])
      ->create();

    $link = (new NewsletterLink($newsletter))->create();
    $open = (new StatisticsOpens($newsletter, $subscriber))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();
    $open->setQueue(null);
    $click->setQueue(null);
    $this->entityManager->flush();

    $statistics = $this->statisticsRepository->getStatistics($newsletter);

    $this->assertSame(1, $statistics->getOpenCount());
    $this->assertSame(1, $statistics->getClickCount());
  }

  /**
   * @group woo
   */
  public function testItIncludesWooCommerceRevenueWithoutQueue(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
      ])
      ->create();

    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();
    $purchase = (new StatisticsWooCommercePurchases($click, [
      'id' => 1,
      'currency' => 'USD',
      'total' => 25,
      'status' => 'completed',
    ]))->create();
    $tableName = $this->entityManager->getClassMetadata(StatisticsWooCommercePurchaseEntity::class)->getTableName();
    $this->entityManager->getConnection()->update($tableName, ['queue_id' => null], ['id' => $purchase->getId()]);

    $revenue = $this->statisticsRepository->getWooCommerceRevenue($newsletter);

    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertSame(1, $revenue->getOrdersCount());
    $this->assertSame(25.0, $revenue->getValue());
  }
}
