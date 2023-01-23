<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;

class TrackerTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var Tracker */
  private $tracker;

  public function _before() {
    parent::_before();
    $this->cleanUp();
    $this->subscriber = (new Subscriber())->create();
    $this->tracker = $this->diContainer->get(Tracker::class);
    // Add dummy option field. This is needed for AUTOMATIC emails analytics
    $newsletterOptionField = new NewsletterOptionFieldEntity();
    $newsletterOptionField->setNewsletterType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletterOptionField->setName('event');
    $this->entityManager->persist($newsletterOptionField);
    $this->entityManager->flush();
  }

  public function testItAddsTrackingData() {
    $data = $this->tracker->addTrackingData(['extensions' => []]);
    expect($data['extensions']['mailpoet'])->notEmpty();
  }

  public function testItAddsCampaignRevenuesForStandardNewsletters() {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_STANDARD)->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter1, $this->createOrderData(3, 'USD', 20));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 20));

    $tracker = $this->diContainer->get(Tracker::class);
    $campaignRevenues = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet']['campaign_revenues'];
    expect($campaignRevenues)->count(2);
    expect($campaignRevenues[0]['campaign_id'])->equals($newsletter1->getId());
    expect($campaignRevenues[0]['revenue'])->equals(30);
    expect($campaignRevenues[0]['campaign_type'])->equals($newsletter1->getType());
    expect($campaignRevenues[0]['orders_count'])->equals(2);

    expect($campaignRevenues[1]['campaign_id'])->equals($newsletter2->getId());
    expect($campaignRevenues[1]['revenue'])->equals(20);
    expect($campaignRevenues[1]['campaign_type'])->equals($newsletter2->getType());
    expect($campaignRevenues[1]['orders_count'])->equals(1);
  }

  public function testItAddsCampaignRevenuesForAutomaticCampaigns() {
    $newsletter1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_WELCOME)->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATIC)->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_AUTOMATION)->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 20));
    $this->createRevenueRecord($newsletter3, $this->createOrderData(3, 'USD', 30));

    $tracker = $this->diContainer->get(Tracker::class);
    $campaignRevenues = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet']['campaign_revenues'];
    expect($campaignRevenues)->count(3);
    expect($campaignRevenues[0]['campaign_id'])->equals($newsletter1->getId());
    expect($campaignRevenues[0]['revenue'])->equals(10);
    expect($campaignRevenues[0]['campaign_type'])->equals($newsletter1->getType());

    expect($campaignRevenues[1]['campaign_id'])->equals($newsletter2->getId());
    expect($campaignRevenues[1]['revenue'])->equals(20);
    expect($campaignRevenues[1]['campaign_type'])->equals($newsletter2->getType());

    expect($campaignRevenues[2]['campaign_id'])->equals($newsletter3->getId());
    expect($campaignRevenues[2]['revenue'])->equals(30);
    expect($campaignRevenues[2]['campaign_type'])->equals($newsletter3->getType());
  }

  public function testItAddsTotalCampaigns() {
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
    expect($campaignsCount)->equals(6);
  }

  public function testItAddsCampaignRevenuesPostNotificationsUnderTheParentId() {
    $notificationParent = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION)->create();
    $notificationHistory1 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->create();
    $notificationHistory1->setParent($notificationParent);
    $notificationHistory2 = (new Newsletter())->withSendingQueue()->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)->create();
    $notificationHistory2->setParent($notificationParent);
    $this->createRevenueRecord($notificationHistory1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($notificationHistory2, $this->createOrderData(2, 'USD', 20));

    $tracker = $this->diContainer->get(Tracker::class);
    $campaignRevenues = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet']['campaign_revenues'];
    expect($campaignRevenues)->count(1);
    expect($campaignRevenues[0]['campaign_id'])->equals($notificationParent->getId());
    expect($campaignRevenues[0]['revenue'])->equals(30);
    expect($campaignRevenues[0]['campaign_type'])->equals($notificationParent->getType());
  }

  /**
   * Because we save the revenue for every recent click, we need to make sure we count the order only once (for the first click)
   */
  public function testItTracksTheRevenueOncePerOrder() {
    $newsletter1 = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();
    $newsletter3 = (new Newsletter())->withSendingQueue()->create();
    $this->createRevenueRecord($newsletter1, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(1, 'USD', 10));
    $this->createRevenueRecord($newsletter2, $this->createOrderData(2, 'USD', 15));
    // Newsletter 3 has only one order and it is already tracked for newsletter 1 so the newsletter 3 will be skipped
    $this->createRevenueRecord($newsletter3, $this->createOrderData(1, 'USD', 10));

    $tracker = $this->diContainer->get(Tracker::class);
    $campaignRevenues = $tracker->addTrackingData(['extensions' => []])['extensions']['mailpoet']['campaign_revenues'];
    expect($campaignRevenues)->count(2);
    expect($campaignRevenues[0]['campaign_id'])->equals($newsletter1->getId());
    expect($campaignRevenues[0]['revenue'])->equals(10);
    expect($campaignRevenues[0]['campaign_type'])->equals($newsletter1->getType());
    expect($campaignRevenues[0]['orders_count'])->equals(1);

    expect($campaignRevenues[1]['campaign_id'])->equals($newsletter2->getId());
    expect($campaignRevenues[1]['revenue'])->equals(15);
    expect($campaignRevenues[1]['campaign_type'])->equals($newsletter2->getType());
    expect($campaignRevenues[1]['orders_count'])->equals(1);
  }

  /**
   * @return array{id: int, currency: string, total: float}
   */
  private function createOrderData(int $id, string $currency, float $total): array {
    return [
      'id' => $id,
      'currency' => $currency,
      'total' => $total,
    ];
  }

  private function createRevenueRecord(NewsletterEntity $newsletter, array $orderData): StatisticsWooCommercePurchaseEntity {
    $link = (new NewsletterLink($newsletter))->create();
    $click = (new StatisticsClicks($link, $this->subscriber))->create();
    return (new StatisticsWooCommercePurchases($click, $orderData))->create();
  }

  private function cleanUp() {
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(StatisticsWooCommercePurchaseEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
  }
}
