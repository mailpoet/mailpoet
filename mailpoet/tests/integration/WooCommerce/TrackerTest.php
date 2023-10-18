<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\NewsletterOptionField;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group woo
 */
class TrackerTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var Tracker */
  private $tracker;

  public function _before(): void {
    parent::_before();
    $this->subscriber = (new Subscriber())->create();
    $this->tracker = $this->diContainer->get(Tracker::class);
    // Add dummy option field. This is needed for AUTOMATIC emails analytics
    (new NewsletterOptionField())->findOrCreate('event', NewsletterEntity::TYPE_AUTOMATIC);
  }

  public function testItAddsTrackingData(): void {
    $data = $this->tracker->addTrackingData(['extensions' => []]);
    verify($data['extensions']['mailpoet'])->notEmpty();
    verify($data['extensions']['mailpoet']['campaigns_count'])->notNull();
  }

  public function testItAddsTheEventOption(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATIC)->create();
    $field = $this->diContainer->get(NewsletterOptionFieldsRepository::class)->findOneBy([
      'name' => NewsletterOptionFieldEntity::NAME_EVENT,
      'newsletterType' => $newsletter1->getType(),
    ]);
    $this->assertInstanceOf(NewsletterOptionFieldEntity::class, $field);
    $option = new NewsletterOptionEntity($newsletter1, $field);
    $option->setValue(FirstPurchase::SLUG);
    $this->diContainer->get(NewsletterOptionsRepository::class)->persist($option);
    $newsletter1->getOptions()->add($option);
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_event'])->equals(FirstPurchase::SLUG);
  }

  public function testItAddsCampaignRevenuesForStandardNewsletters(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter1, $this->createOrderData(3, 'USD', 20));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 20));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_revenue'])->equals(30);
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_type'])->equals($newsletter1->getType());
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_orders_count'])->equals(2);

    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_revenue'])->equals(20);
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_type'])->equals($newsletter2->getType());
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_orders_count'])->equals(1);
  }

  public function testItAddsOnlyRevenuesFromCompletedOrders(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10, 'completed'));
    $this->createRevenueRecord($newsletter1, $this->createOrderData(3, 'USD', 20, 'processing'));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_revenue'])->equals(10);
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_orders_count'])->equals(1);
  }

  public function testItAddsCampaignRevenuesForAutomaticCampaigns(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_WELCOME)->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATIC)->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATION)->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 20));
    $this->createRevenueRecord($newsletter3, $this->createOrderData(3, 'USD', 30));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_revenue'])->equals(10);
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_type'])->equals($newsletter1->getType());
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_orders_count'])->equals(1);

    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_revenue'])->equals(20);
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_type'])->equals($newsletter2->getType());
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_orders_count'])->equals(1);

    verify($mailPoetData['campaign_' . $newsletter3->getId() . '_revenue'])->equals(30);
    verify($mailPoetData['campaign_' . $newsletter3->getId() . '_type'])->equals($newsletter3->getType());
    verify($mailPoetData['campaign_' . $newsletter3->getId() . '_orders_count'])->equals(1);
  }

  public function testItAddsTotalCampaigns(): void {
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_WELCOME)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATIC)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATION)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_RE_ENGAGEMENT)->withStatus(NewsletterEntity::STATUS_ACTIVE)->create();
    // Is not counted as a campaign
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->withStatus(NewsletterEntity::STATUS_DRAFT)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_WELCOME)->withStatus(NewsletterEntity::STATUS_DRAFT)->create();
    (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->create();
    $tracker = $this->diContainer->get(Tracker::class);
    $campaignsCount = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet']['campaigns_count'];
    verify($campaignsCount)->equals(6);
  }

  public function testItAddsCampaignRevenuesPostNotificationsUnderTheParentId(): void {
    $notificationParent = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION)->create();
    $notificationHistory1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->create();
    $notificationHistory1->setParent($notificationParent);
    $notificationHistory2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->create();
    $notificationHistory2->setParent($notificationParent);
    $this->createRevenueRecord($notificationHistory1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($notificationHistory2, $this->createOrderData(2, 'USD', 20));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $notificationParent->getId() . '_revenue'])->equals(30);
    verify($mailPoetData['campaign_' . $notificationParent->getId() . '_type'])->equals($notificationParent->getType());
    verify($mailPoetData['campaign_' . $notificationParent->getId() . '_orders_count'])->equals(2);

    verify($mailPoetData)->arrayHasNotKey('campaign_' . $notificationHistory1->getId() . '_revenue');
    verify($mailPoetData)->arrayHasNotKey('campaign_' . $notificationHistory2->getId() . '_revenue');
  }

  public function testItTracksOnlyTheMainShopCurrency(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 20));
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'CZK', 10));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_revenue'])->equals(20);
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_type'])->equals($newsletter1->getType());
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_orders_count'])->equals(1);
  }

  /**
   * Because we save the revenue for every recent click, we need to make sure we count the order only once (for the first click)
   */
  public function testItTracksTheRevenueOncePerOrder(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 15));
    // Newsletter 3 has only one order and it is already tracked for newsletter 1 so the newsletter 3 will be skipped
    $this->createRevenueRecord($newsletter3, $this->createOrderData(1, 'USD', 10));

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_revenue'])->equals(10);
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_type'])->equals($newsletter1->getType());
    verify($mailPoetData['campaign_' . $newsletter1->getId() . '_orders_count'])->equals(1);

    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_revenue'])->equals(15);
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_type'])->equals($newsletter2->getType());
    verify($mailPoetData['campaign_' . $newsletter2->getId() . '_orders_count'])->equals(1);

    verify($mailPoetData)->arrayHasNotKey('campaign_' . $newsletter3->getId() . '_revenue');
  }

  public function testItTracksTheRevenueOfDeletedCampaigns(): void {
    $newsletter1 = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 10));
    $this->createRevenueRecord($newsletter3, $this->createOrderData(3, 'USD', 10));

    $newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $newsletterRepository->bulkDelete([$newsletter1->getId(), $newsletter2->getId()]);

    $tracker = $this->diContainer->get(Tracker::class);
    $mailPoetData = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet'];

    verify($mailPoetData['campaign_0_revenue'])->equals(20);
    verify($mailPoetData['campaign_0_type'])->equals('unknown');
    verify($mailPoetData['campaign_0_orders_count'])->equals(2);
  }

  /**
   * @return array{id: int, currency: string, total: float}
   */
  private function createOrderData(int $id, string $currency, float $total, string $status = 'completed'): array {
    return [
      'id' => $id,
      'currency' => $currency,
      'total' => $total,
      'status' => $status,
    ];
  }

  private function createRevenueRecord(NewsletterEntity $newsletter, array $orderData): StatisticsWooCommercePurchaseEntity {
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $this->subscriber))->create();
    return (new StatisticsWooCommercePurchases($click, $orderData))->create();
  }
}
