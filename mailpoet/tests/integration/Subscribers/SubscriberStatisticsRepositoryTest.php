<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\Statistics;

use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WooCommerce\OrderAttributionFields;
use MailPoet\WooCommerce\OrderAttributionWriter;
use MailPoetVendor\Carbon\Carbon;
use WC_Order;

/**
 * @group woo
 */
class SubscriberStatisticsRepositoryTest extends \MailPoetTest {
  /** @var SubscriberStatisticsRepository */
  private $repository;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(SubscriberStatisticsRepository::class);
    $this->settings = SettingsController::getInstance();
    (new Features())->withFeatureDisabled(FeaturesController::FEATURE_WOO_BACKED_REVENUE_REPORTING);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    delete_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
  }

  public function testItFetchesClickCount(): void {
    $yearAgo = Carbon::now()->subYear();
    $monthAgo = Carbon::now()->subMonth();
    $fiveYearsAgo = Carbon::now()->subYears(5);

    $subscriber = (new Subscriber())->create();

    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $sendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($monthAgo)->create();
    $click = (new StatisticsClicks($link, $subscriber))
      ->withCreatedAt($monthAgo)
      ->create();

    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $link2 = (new NewsletterLink($newsletter2))->create();
    $sendStat2 = (new StatisticsNewsletters($newsletter2, $subscriber))->withSentAt($yearAgo)->create();
    $click2 = (new StatisticsClicks($link2, $subscriber))
      ->withCreatedAt($yearAgo)
      ->create();

    $newsletter3 = (new Newsletter())->withSendingQueue()->create();
    $link3 = (new NewsletterLink($newsletter3))->create();
    $sendStat3 = (new StatisticsNewsletters($newsletter3, $subscriber))->withSentAt($fiveYearsAgo)->create();
    $click3 = (new StatisticsClicks($link3, $subscriber))
      ->withCreatedAt($fiveYearsAgo)
      ->create();

    $lifetimeCount = $this->repository->getStatisticsClickCount($subscriber, null);
    verify($lifetimeCount)->equals(3);

    $yearCount = $this->repository->getStatisticsClickCount($subscriber, $yearAgo);
    verify($yearCount)->equals(2);

    $monthCount = $this->repository->getStatisticsClickCount($subscriber, $monthAgo);
    verify($monthCount)->equals(1);

    verify($this->repository->getStatisticsClickCount($subscriber, Carbon::now()->subDays(27)))->equals(0);
  }

  public function testItFetchesOpenCount(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $yearAgo = Carbon::now()->subYear();
    $open = (new StatisticsOpens($newsletter, $subscriber))->withCreatedAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($yearAgo)->create();

    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(1);
    verify($this->repository->getStatisticsOpenCount($subscriber, $yearAgo))->equals(1);
    verify($this->repository->getStatisticsOpenCount($subscriber, Carbon::now()->subMonth()))->equals(0);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, null))->equals(0);
  }

  public function testItFetchesOpenCountMergedWithMachineCount(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $yearAgo = Carbon::now()->subYear();
    $open = (new StatisticsOpens($newsletter, $subscriber))->withCreatedAt($yearAgo)->create();
    $open2 = (new StatisticsOpens($newsletter2, $subscriber))->withMachineUserAgentType()->withCreatedAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($yearAgo)->create();
    $newsletterSendStat2 = (new StatisticsNewsletters($newsletter2, $subscriber))->withSentAt($yearAgo)->create();

    $this->settings->set('tracking.opens', TrackingConfig::OPENS_MERGED);

    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(2);
    verify($this->repository->getStatisticsOpenCount($subscriber, $yearAgo))->equals(2);
    verify($this->repository->getStatisticsOpenCount($subscriber, Carbon::now()->subMonth()))->equals(0);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, null))->equals(1);
  }

  public function testItFetchesOpenCountSeparatedFromMachineCount(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $yearAgo = Carbon::now()->subYear();
    $open = (new StatisticsOpens($newsletter, $subscriber))->withCreatedAt($yearAgo)->create();
    $open2 = (new StatisticsOpens($newsletter2, $subscriber))->withMachineUserAgentType()->withCreatedAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($yearAgo)->create();
    $newsletterSendStat2 = (new StatisticsNewsletters($newsletter2, $subscriber))->withSentAt($yearAgo)->create();

    $this->settings->set('tracking.opens', TrackingConfig::OPENS_SEPARATED);

    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(1);
    verify($this->repository->getStatisticsOpenCount($subscriber, $yearAgo))->equals(1);
    verify($this->repository->getStatisticsOpenCount($subscriber, Carbon::now()->subMonth()))->equals(0);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, null))->equals(1);
  }

  public function testItFetchesMachineOpenCount(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $yearAgo = Carbon::now()->subYear();
    $open = (new StatisticsOpens($newsletter, $subscriber))->withMachineUserAgentType()->withCreatedAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($yearAgo)->create();

    verify($this->repository->getStatisticsMachineOpenCount($subscriber, null))->equals(1);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, $yearAgo))->equals(1);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, Carbon::now()->subMonth()))->equals(0);
    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(1); // Merged with machine count
    $this->settings->set('tracking.opens', TrackingConfig::OPENS_SEPARATED);
    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(0); // Separated from machine count
  }

  public function testItFetchesTotalSentCount(): void {
    $subscriber = (new Subscriber())->create();

    $twoYearsAgo = Carbon::now()->subYears(2);
    $yearAgo = Carbon::now()->subYear();
    $monthAgo = Carbon::now()->subMonth();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($twoYearsAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter2, $subscriber))->withSentAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter3, $subscriber))->withSentAt($monthAgo)->create();

    verify($this->repository->getTotalSentCount($subscriber, $twoYearsAgo))->equals(3);
    verify($this->repository->getTotalSentCount($subscriber, $yearAgo))->equals(2);
    verify($this->repository->getTotalSentCount($subscriber, $monthAgo))->equals(1);
    verify($this->repository->getTotalSentCount($subscriber, Carbon::now()->subDays(27)))->equals(0);
  }

  public function testItGetsUnknownEngagementScoreTypeForSubscribersWithoutEnoughEmails(): void {
    $subscriber = (new Subscriber())->create();
    $subscriberWithTwoEmails = (new Subscriber())->create();
    $this->createSentEmails($subscriberWithTwoEmails, 2, Carbon::now()->subMonth());

    verify($this->repository->getEngagementScoreType($subscriber))->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_UNKNOWN);
    verify($this->repository->getEngagementScoreType($subscriberWithTwoEmails))->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_UNKNOWN);
  }

  public function testItGetsDormantEngagementScoreTypeForSubscribersWithoutEnoughRecentEmails(): void {
    $subscriber = (new Subscriber())->create();
    $this->createSentEmails($subscriber, 3, Carbon::now()->subMonths(13));

    verify($this->repository->getEngagementScoreType($subscriber))->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_DORMANT);
  }

  public function testItGetsEngagementScoreTypeFromScore(): void {
    $low = (new Subscriber())->withEngagementScore(10)->create();
    $good = (new Subscriber())->withEngagementScore(35)->create();
    $excellent = (new Subscriber())->withEngagementScore(75)->create();

    $scoreTypes = $this->repository->getEngagementScoreTypes([$low, $good, $excellent]);

    verify($scoreTypes[(int)$low->getId()])->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_LOW);
    verify($scoreTypes[(int)$good->getId()])->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_GOOD);
    verify($scoreTypes[(int)$excellent->getId()])->equals(SubscriberStatisticsRepository::ENGAGEMENT_SCORE_EXCELLENT);
  }

  public function testItFetchesWooCommerceRevenueData(): void {
    $subscriber = (new Subscriber())->create();
    $twoYearsAgo = Carbon::now()->subYears(2);
    $yearAgo = Carbon::now()->subYear();
    $monthAgo = Carbon::now()->subMonth();

    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))
      ->create();

    (new StatisticsWooCommercePurchases($click, [
      'id' => 1,
      'currency' => 'USD',
      'total' => 10.00,
    ]))->withCreatedAt($twoYearsAgo)->create();
    (new StatisticsWooCommercePurchases($click, [
      'id' => 2,
      'currency' => 'USD',
      'total' => 20.00,
    ]))->withCreatedAt($yearAgo)->create();
    (new StatisticsWooCommercePurchases($click, [
      'id' => 3,
      'currency' => 'USD',
      'total' => 30.00,
    ]))->withCreatedAt($monthAgo)->create();

    $twoYearsAgoResult = $this->repository->getWooCommerceRevenue($subscriber, $twoYearsAgo);
    $this->assertInstanceOf(WooCommerceRevenue::class, $twoYearsAgoResult);
    verify($twoYearsAgoResult->getOrdersCount())->equals(3);
    verify($twoYearsAgoResult->getValue())->equals(60.00);

    $yearAgoResult = $this->repository->getWooCommerceRevenue($subscriber, $yearAgo);
    $this->assertInstanceOf(WooCommerceRevenue::class, $yearAgoResult);
    verify($yearAgoResult->getOrdersCount())->equals(2);
    verify($yearAgoResult->getValue())->equals(50.00);

    $monthAgoResult = $this->repository->getWooCommerceRevenue($subscriber, $monthAgo);
    $this->assertInstanceOf(WooCommerceRevenue::class, $monthAgoResult);
    verify($monthAgoResult->getOrdersCount())->equals(1);
    verify($monthAgoResult->getValue())->equals(30.00);

    $daysAgoResult = $this->repository->getWooCommerceRevenue($subscriber, Carbon::now()->subDays(27));
    $this->assertInstanceOf(WooCommerceRevenue::class, $daysAgoResult);
    verify($daysAgoResult->getOrdersCount())->equals(0);
    verify($daysAgoResult->getValue())->equals(0.00);
  }

  public function testItFetchesWooBackedSubscriberRevenueWhenFeatureFlagIsEnabled(): void {
    $this->enableWooBackedRevenueReadModel();
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();

    $order = $this->createCompletedOrderWithAttribution($click, $subscriber, 30);

    $result = $this->repository->getWooCommerceRevenue($subscriber, Carbon::now()->subHour());

    $this->assertInstanceOf(WooCommerceRevenue::class, $result);
    verify($result->getOrdersCount())->equals(1);
    verify($result->getValue())->equals((float)$order->get_remaining_refund_amount());
  }

  public function testWooBackedSubscriberRevenueSkipsOrdersWhenMailPoetDidNotWinSource(): void {
    $this->enableWooBackedRevenueReadModel();
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();

    $order = $this->createCompletedOrderWithAttribution($click, $subscriber, 40);
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'google');
    $order->save_meta_data();

    (new StatisticsWooCommercePurchases($click, [
      'id' => $order->get_id(),
      'currency' => 'USD',
      'total' => 18.00,
    ]))->withCreatedAt(new \DateTimeImmutable('-30 minutes'))->create();

    $result = $this->repository->getWooCommerceRevenue($subscriber, Carbon::now()->subHour());

    $this->assertInstanceOf(WooCommerceRevenue::class, $result);
    verify($result->getOrdersCount())->equals(0);
    verify($result->getValue())->equals(0.00);
  }

  public function testWooBackedSubscriberRevenueFallsBackToLegacyWhenSubscriberMetaIsMissing(): void {
    $this->enableWooBackedRevenueReadModel();
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();

    (new StatisticsWooCommercePurchases($click, [
      'id' => 2001,
      'currency' => 'USD',
      'total' => 18.00,
    ]))->withCreatedAt(new \DateTimeImmutable('-30 minutes'))->create();

    $result = $this->repository->getWooCommerceRevenue($subscriber, Carbon::now()->subHour());

    $this->assertInstanceOf(WooCommerceRevenue::class, $result);
    verify($result->getOrdersCount())->equals(1);
    verify($result->getValue())->equals(18.00);
  }

  public function testWooBackedSubscriberRevenueFallsBackToLegacyWhenOrderAttributionIsPartial(): void {
    $this->enableWooBackedRevenueReadModel();
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $subscriber))->create();
    $queue = $click->getQueue();
    $this->assertNotNull($queue);

    // Post-boundary Woo order carrying click + newsletter attribution but no
    // subscriber meta: the Woo subscriber path must skip it and the legacy
    // purchase row must supply the value instead of it being dropped.
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email('partial-attribution@example.com');
    $order->set_currency('USD');
    $order->set_total('40');
    $order->set_status('completed');
    $order->save();
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID), (string)$click->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID), (string)$newsletter->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_QUEUE_ID), (string)$queue->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'utm');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'mailpoet');
    $order->save_meta_data();

    (new StatisticsWooCommercePurchases($click, [
      'id' => $order->get_id(),
      'currency' => 'USD',
      'total' => 18.00,
    ]))->withCreatedAt(new \DateTimeImmutable('-30 minutes'))->create();

    $result = $this->repository->getWooCommerceRevenue($subscriber, Carbon::now()->subHour());

    $this->assertInstanceOf(WooCommerceRevenue::class, $result);
    verify($result->getOrdersCount())->equals(1);
    verify($result->getValue())->equals(18.00);
  }

  private function enableWooBackedRevenueReadModel(): void {
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_WOO_BACKED_REVENUE_REPORTING);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    update_option(OrderAttributionWriter::WRITES_STARTED_AT_OPTION, gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS));
  }

  private function createCompletedOrderWithAttribution(
    StatisticsClickEntity $click,
    SubscriberEntity $subscriber,
    float $total
  ): WC_Order {
    $newsletter = $click->getNewsletter();
    $queue = $click->getQueue();
    $this->assertNotNull($newsletter);
    $this->assertNotNull($queue);

    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email('manual-attribution@example.com');
    $order->set_currency('USD');
    $order->set_total((string)$total);
    $order->set_status('completed');
    $order->save();
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID), (string)$click->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID), (string)$newsletter->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_QUEUE_ID), (string)$queue->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID), (string)$subscriber->getId());
    $order->update_meta_data(OrderAttributionFields::getMetaKey('source_type'), 'utm');
    $order->update_meta_data(OrderAttributionFields::getMetaKey('utm_source'), 'mailpoet');
    $order->save_meta_data();
    return $order;
  }

  private function createSentEmails(SubscriberEntity $subscriber, int $count, Carbon $sentAt): void {
    for ($i = 0; $i < $count; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->create();
      (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($sentAt)->create();
    }
  }
}
