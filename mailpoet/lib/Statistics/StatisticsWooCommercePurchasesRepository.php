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
}
