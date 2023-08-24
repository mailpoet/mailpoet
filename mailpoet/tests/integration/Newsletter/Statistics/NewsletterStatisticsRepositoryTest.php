<?php declare(strict_types = 1);

namespace integration\Newsletter\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Statistics\StatisticsWooCommercePurchasesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\Subscriber;

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

  /** @var StatisticsClickEntity */
  private $click1;

  /** @var StatisticsClickEntity */
  private $click2;

  public function _before() {
    $this->testee = $this->diContainer->get(NewsletterStatisticsRepository::class);
    $this->revenueRepository = $this->diContainer->get(StatisticsWooCommercePurchasesRepository::class);
    $this->newsletter = (new Newsletter())->withSendingQueue()->create();
    $this->assertInstanceOf(NewsletterEntity::class, $this->newsletter);
    $this->subscriber = (new Subscriber())->create();

    $link = (new NewsletterLink($this->newsletter))->create();
    $this->click1 = (new StatisticsClicks($link, $this->subscriber))->create();
    $link = (new NewsletterLink($this->newsletter))->create();
    $this->click2 = (new StatisticsClicks($link, $this->subscriber))->create();
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
}
