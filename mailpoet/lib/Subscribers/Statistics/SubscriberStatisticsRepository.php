<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<SubscriberEntity>
 */
class SubscriberStatisticsRepository extends Repository {

  /** @var WCHelper */
  private $wcHelper;

  public function __construct(
    EntityManager $entityManager,
    WCHelper $wcHelper
  ) {
    parent::__construct($entityManager);
    $this->wcHelper = $wcHelper;
  }

  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  public function getStatistics(SubscriberEntity $subscriber) {
    return new SubscriberStatistics(
      $this->getStatisticsClickCount($subscriber),
      $this->getStatisticsOpenCount($subscriber),
      $this->getStatisticsMachineOpenCount($subscriber),
      $this->getTotalSentCount($subscriber),
      $this->getWooCommerceRevenue($subscriber)
    );
  }

  private function getStatisticsClickCount(SubscriberEntity $subscriber): int {
    return (int)$this->getStatisticsCountQuery(StatisticsClickEntity::class, $subscriber)
      ->getQuery()
      ->getSingleScalarResult();
  }

  private function getStatisticsOpenCount(SubscriberEntity $subscriber): int {
    return (int)$this->getStatisticsCountQuery(StatisticsOpenEntity::class, $subscriber)
      ->andWhere('(stats.userAgentType = :userAgentType) OR (stats.userAgentType IS NULL)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN)
      ->getQuery()
      ->getSingleScalarResult();
  }

  private function getStatisticsMachineOpenCount(SubscriberEntity $subscriber): int {
    return (int)$this->getStatisticsCountQuery(StatisticsOpenEntity::class, $subscriber)
      ->andWhere('(stats.userAgentType = :userAgentType)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE)
      ->getQuery()
      ->getSingleScalarResult();
  }

  private function getTotalSentCount(SubscriberEntity $subscriber): int {
    return $this->getStatisticsCountQuery(StatisticsNewsletterEntity::class, $subscriber)
      ->getQuery()
      ->getSingleScalarResult();
  }

  private function getStatisticsCountQuery(string $entityName, SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT stats.newsletter) as cnt')
      ->from($entityName, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber);
  }

  private function getWooCommerceRevenue(SubscriberEntity $subscriber) {
    if (!$this->wcHelper->isWooCommerceActive()) {
      return null;
    }

    $currency = $this->wcHelper->getWoocommerceCurrency();
    $purchases = $this->entityManager->createQueryBuilder()
      ->select('stats.orderPriceTotal')
      ->from(StatisticsWooCommercePurchaseEntity::class, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->andWhere('stats.orderCurrency = :currency')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('currency', $currency)
      ->groupBy('stats.orderId, stats.orderPriceTotal')
      ->getQuery()
      ->getResult();
    $sum = array_sum(array_column($purchases, 'orderPriceTotal'));
    return new WooCommerceRevenue(
      $currency,
      (float)$sum,
      count($purchases),
      $this->wcHelper
    );
  }
}
