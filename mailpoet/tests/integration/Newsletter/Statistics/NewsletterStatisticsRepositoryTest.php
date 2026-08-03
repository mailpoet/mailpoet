<?php declare(strict_types = 1);

namespace integration\Newsletter\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WooCommerce\OrderAttributionFields;
use MailPoet\WooCommerce\OrderAttributionWriter;
use WC_Order;

/**
 * @group woo
 */

class NewsletterStatisticsRepositoryTest extends \MailPoetTest {

  /** @var NewsletterStatisticsRepository */
  private $testee;

  /** @var StatisticsWooCommercePurchasesRepository */
  private $revenueRepository;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var \MailPoet\Entities\SubscriberEntity */
  private $subscriber;

  /** @var \MailPoet\Entities\SubscriberEntity */
  private $subscriber2;

  /** @var StatisticsClickEntity */
  private $click1;

  /** @var StatisticsClickEntity */
  private $click2;

  public function _before() {
    $this->testee = $this->diContainer->get(NewsletterStatisticsRepository::class);
    $this->revenueRepository = $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class);
    add_filter('mailpoet_woo_backed_revenue_reporting', '__return_false');
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    $this->newsletter = (new Newsletter())->withSendingQueue()->create();
    $this->assertInstanceOf(NewsletterEntity::class, $this->newsletter);
    $this->subscriber = (new Subscriber())->create();
    $this->subscriber2 = (new Subscriber())->create();

    $link = (new NewsletterLink($this->newsletter))->create();
    $this->click1 = (new StatisticsClicks($link, $this->subscriber))->create();
    $link = (new NewsletterLink($this->newsletter))->create();
    $this->click2 = (new StatisticsClicks($link, $this->subscriber))->create();
  }

  public function testItGetsMergedOpens() {
    $open = (new StatisticsOpens($this->newsletter, $this->subscriber))->create();
    $open2 = (new StatisticsOpens($this->newsletter, $this->subscriber2))->withMachineUserAgentType()->create();
    SettingsController::getInstance()->set('tracking.opens', TrackingConfig::OPENS_MERGED);
    $count = $this->testee->getStatisticsOpenCount($this->newsletter);
    verify($count)->equals(2);
  }

  public function testItGetsSeparatedOpens() {
    $open = (new StatisticsOpens($this->newsletter, $this->subscriber))->create();
    $open2 = (new StatisticsOpens($this->newsletter, $this->subscriber2))->withMachineUserAgentType()->create();
    SettingsController::getInstance()->set('tracking.opens', TrackingConfig::OPENS_SEPARATED);
    $count = $this->testee->getStatisticsOpenCount($this->newsletter);
    verify($count)->equals(1);
  }

  public function testItCountsCampaignSendsFromTheSendingQueues() {
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 5, 'count_total' => 5])
      ->create();

    verify($this->testee->getTotalSentCount($newsletter))->equals(5);
  }

  /** @dataProvider repeatedlySentTypeProvider */
  public function testItCountsRepeatedlySentEmailsFromTheSendingStatistics(string $type) {
    $newsletter = (new Newsletter())
      ->withType($type)
      ->withSendingQueue()
      ->create();

    // the queue only accounts for the single most recent send
    $this->recordSends($newsletter, 3);

    verify($this->testee->getTotalSentCount($newsletter))->equals(3);
  }

  public function repeatedlySentTypeProvider(): array {
    return [
      'welcome' => [NewsletterEntity::TYPE_WELCOME],
      'automatic' => [NewsletterEntity::TYPE_AUTOMATIC],
      'automation' => [NewsletterEntity::TYPE_AUTOMATION],
      'automation transactional' => [NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL],
      'automation notification' => [NewsletterEntity::TYPE_AUTOMATION_NOTIFICATION],
      're-engagement' => [NewsletterEntity::TYPE_RE_ENGAGEMENT],
    ];
  }

  public function testItStillCountsRepeatedlySentEmailsWhenTheScheduledTaskIsGone() {
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->withSendingQueue()
      ->create();
    $this->recordSends($newsletter, 3);

    $this->deleteScheduledTask($newsletter);

    verify($this->testee->getTotalSentCount($newsletter))->equals(3);
  }

  public function testItKeepsTheOpenRateWithinOneHundredPercentWhenTheQueueChainIsBroken() {
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->withSendingQueue()
      ->create();
    $subscribers = $this->recordSends($newsletter, 3);
    foreach ($subscribers as $subscriber) {
      (new StatisticsOpens($newsletter, $subscriber))->create();
    }

    $this->deleteScheduledTask($newsletter);

    $statistics = $this->testee->getStatistics($newsletter);
    verify($statistics->getOpenCount())->equals(3);
    verify($statistics->getTotalSentCount())->equals(3);
    $this->assertLessThanOrEqual($statistics->getTotalSentCount(), $statistics->getOpenCount());
  }

  public function testItCountsEachNewsletterTypeFromItsOwnSourceInABatch() {
    $campaign = (new Newsletter())
      ->withSendingQueue(['count_processed' => 5, 'count_total' => 5])
      ->create();
    $automation = (new Newsletter())
      ->withAutomationType()
      ->withSendingQueue()
      ->create();
    $this->recordSends($automation, 3);

    $statistics = $this->testee->getBatchStatistics([$campaign, $automation]);

    verify($statistics[$campaign->getId()]->getTotalSentCount())->equals(5);
    verify($statistics[$automation->getId()]->getTotalSentCount())->equals(3);
  }

  public function testItCountsRepeatedlySentEmailsWithinTheGivenTimeSpan() {
    $newsletter = (new Newsletter())
      ->withAutomationType()
      ->withSendingQueue()
      ->create();
    $subscribers = $this->recordSends($newsletter, 3);

    (new StatisticsNewsletters($newsletter, $subscribers[0]))
      ->withSentAt(new \DateTimeImmutable('-6 months'))
      ->create();

    $statistics = $this->testee->getBatchStatistics(
      [$newsletter],
      new \DateTimeImmutable('-1 month'),
      new \DateTimeImmutable('+1 day'),
      ['totals']
    );

    verify($statistics[$newsletter->getId()]->getTotalSentCount())->equals(3);
  }

  public function testItLeavesCampaignsCountedFromTheQueueChain() {
    $newsletter = (new Newsletter())
      ->withSendingQueue(['count_processed' => 5, 'count_total' => 5])
      ->create();

    $this->deleteScheduledTask($newsletter);

    // campaigns keep using the queue chain, so a missing task still hides the send
    verify($this->testee->getTotalSentCount($newsletter))->equals(0);
  }

  public function testItGetsOnlyStatisticsWithTheCorrectStatus() {
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $toBeFound = new StatisticsWooCommercePurchaseEntity(
      $this->newsletter,
      $queue,
      $this->click1,
      1,
      'USD',
      10,
      'completed'
    );
    $toBeFound->setSubscriber($this->subscriber);
    $this->revenueRepository->persist($toBeFound);

    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $notToBeFound = new StatisticsWooCommercePurchaseEntity(
      $this->newsletter,
      $queue,
      $this->click2,
      1,
      'USD',
      20,
      'non_completed'
    );
    $notToBeFound->setSubscriber($this->subscriber);
    $this->revenueRepository->persist($notToBeFound);
    $this->revenueRepository->flush();

    $revenue = $this->testee->getWooCommerceRevenue($this->newsletter);
    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertEquals(1, $revenue->getOrdersCount());
    $this->assertEquals(10, $revenue->getValue());
  }

  public function testWooBackedRevenueIsIgnoredWhenFeatureFlagIsDisabled(): void {
    // Order attributed to mailpoet in Woo's standard source but with no legacy
    // purchase row. With the read flag off the Woo-backed read is skipped and the
    // legacy read finds nothing, so the order is not reported.
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email('manual-attribution@example.com');
    $order->set_currency('USD');
    $order->set_total('15');
    $order->set_status('completed');
    $order->save();
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'utm');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'mailpoet');
    $order->save_meta_data();

    $revenue = $this->testee->getWooCommerceRevenue($this->newsletter);

    $this->assertNull($revenue);
  }

  public function testWooBackedRevenueBlendsHistoricalLegacyWithPostBoundaryWooAttribution(): void {
    $this->enableWooBackedRevenueReadModel();
    (new StatisticsWooCommercePurchases($this->click1, [
      'id' => 1001,
      'currency' => 'USD',
      'total' => 10.00,
    ]))->withCreatedAt(new \DateTimeImmutable('-2 hours'))->create();

    $order = $this->createCompletedOrderWithAttribution($this->click1, 'manual-attribution@example.com', 20);
    $refund = wc_create_refund([
      'order_id' => $order->get_id(),
      'amount' => 5,
    ]);
    $this->assertNotInstanceOf(\WP_Error::class, $refund);

    $revenue = $this->testee->getWooCommerceRevenue($this->newsletter);

    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertEquals(2, $revenue->getOrdersCount());
    $this->assertEquals(25, $revenue->getValue());
  }

  public function testWooBackedRevenueIncludesReplayQueueAttribution(): void {
    $this->enableWooBackedRevenueReadModel();
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $queue->setMeta([NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true]);
    $this->entityManager->flush();

    $this->createCompletedOrderWithAttribution($this->click1, 'replay-attribution@example.com', 20);

    $revenue = $this->testee->getWooCommerceRevenue($this->newsletter);

    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertEquals(1, $revenue->getOrdersCount());
    $this->assertEquals(20, $revenue->getValue());
  }

  public function testWooBackedRevenueFallsBackToLegacyWhenPostBoundaryWooAttributionIsMissing(): void {
    $this->enableWooBackedRevenueReadModel();
    (new StatisticsWooCommercePurchases($this->click1, [
      'id' => 1002,
      'currency' => 'USD',
      'total' => 12.00,
    ]))->withCreatedAt(new \DateTimeImmutable('-30 minutes'))->create();

    $revenue = $this->testee->getWooCommerceRevenue($this->newsletter);

    $this->assertInstanceOf(WooCommerceRevenue::class, $revenue);
    $this->assertEquals(1, $revenue->getOrdersCount());
    $this->assertEquals(12, $revenue->getValue());
  }

  /**
   * @return \MailPoet\Entities\SubscriberEntity[]
   */
  private function recordSends(NewsletterEntity $newsletter, int $count): array {
    $subscribers = [];
    for ($i = 0; $i < $count; $i++) {
      $subscriber = (new Subscriber())->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->create();
      $subscribers[] = $subscriber;
    }
    return $subscribers;
  }

  private function deleteScheduledTask(NewsletterEntity $newsletter): void {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $task = $queue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 't')
      ->where('t.id = :id')
      ->setParameter('id', $task->getId())
      ->getQuery()
      ->execute();
  }

  private function enableWooBackedRevenueReadModel(): void {
    remove_filter('mailpoet_woo_backed_revenue_reporting', '__return_false');
    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS));
  }

  private function createCompletedOrderWithAttribution(StatisticsClickEntity $click, string $billingEmail, float $total): WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($billingEmail);
    $order->set_currency('USD');
    $order->set_total((string)$total);
    $order->set_status('completed');
    $order->save();
    // The Woo-backed reader recovers per-order newsletter/subscriber/queue detail from
    // the legacy purchase row (joined on order_id) and counts the order only when the
    // standard source resolved to mailpoet, so seed both.
    (new StatisticsWooCommercePurchases($click, [
      'id' => $order->get_id(),
      'currency' => 'USD',
      'total' => $total,
    ]))->create();
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'utm');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'mailpoet');
    $order->save_meta_data();
    return $order;
  }
}
