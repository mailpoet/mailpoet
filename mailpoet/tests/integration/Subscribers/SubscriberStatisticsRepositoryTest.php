<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\Statistics;

use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class SubscriberStatisticsRepositoryTest extends \MailPoetTest {
  /** @var SubscriberStatisticsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(SubscriberStatisticsRepository::class);
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

  public function testItFetchesMachineOpenCount(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $yearAgo = Carbon::now()->subYear();
    $open = (new StatisticsOpens($newsletter, $subscriber))->withMachineUserAgentType()->withCreatedAt($yearAgo)->create();
    $newsletterSendStat = (new StatisticsNewsletters($newsletter, $subscriber))->withSentAt($yearAgo)->create();

    verify($this->repository->getStatisticsMachineOpenCount($subscriber, null))->equals(1);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, $yearAgo))->equals(1);
    verify($this->repository->getStatisticsMachineOpenCount($subscriber, Carbon::now()->subMonth()))->equals(0);
    verify($this->repository->getStatisticsOpenCount($subscriber, null))->equals(0);
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
}
