<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;

/**
 * @extends Repository<StatisticsWooCommercePurchaseEntity>
 */
class StatisticsWooCommercePurchasesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsWooCommercePurchaseEntity::class;
  }

  public function createOrUpdateByClickDataAndOrder(StatisticsClickEntity $click, \WC_Order $order) {
    // search by subscriber and newsletter IDs (instead of click itself) to avoid duplicities
    // when a new click from the subscriber appeared since last tracking for given newsletter
    // (this will keep the originally tracked click - likely the click that led to the order)
    $statistics = $this->findOneBy([
      'orderId' => $order->get_id(),
      'subscriber' => $click->getSubscriber(),
      'newsletter' => $click->getNewsletter(),
    ]);

    if (!$statistics instanceof StatisticsWooCommercePurchaseEntity) {
      $newsletter = $click->getNewsletter();
      $queue = $click->getQueue();
      if ((!$newsletter instanceof NewsletterEntity) || (!$queue instanceof SendingQueueEntity)) return;
      $statistics = new StatisticsWooCommercePurchaseEntity(
        $newsletter,
        $queue,
        $click,
        $order->get_id(),
        $order->get_currency(),
        $order->get_total()
      );
      $this->persist($statistics);
    } else {
      $statistics->setOrderCurrency($order->get_currency());
      $statistics->setOrderPriceTotal($order->get_total());
    }
    $statistics->setSubscriber($click->getSubscriber());
    $this->flush();
  }

  public function getRevenuesByCampaigns() {
    $revenueStatsTable = $this->entityManager->getClassMetadata(StatisticsWooCommercePurchaseEntity::class)->getTableName();
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();

    $data = $this->entityManager->getConnection()->executeQuery('
      SELECT
        SUM(swp.order_price_total) AS revenue,
        COALESCE(n.parent_id, n.id) AS campaign_id,
        (CASE WHEN n.type = :notification_history_type THEN :notification_type ELSE n.type END) AS campaign_type,
        COUNT(order_id) as orders_count
      FROM ' . $revenueStatsTable . ' swp
        JOIN ' . $newsletterTable . ' n ON
          n.id = swp.newsletter_id
          AND swp.click_id IN (SELECT MIN(click_id) FROM ' . $revenueStatsTable . ' ss GROUP BY order_id)
      GROUP BY campaign_id, n.type;
    ', [
      'notification_history_type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      'notification_type' => NewsletterEntity::TYPE_NOTIFICATION,
    ])->fetchAllAssociative();

    $data = array_map(function($row) {
      $row['revenue'] = round(floatval($row['revenue']), 2);
      $row['orders_count'] = intval($row['orders_count']);
      return $row;
    }, $data);
    return $data;
  }
}
