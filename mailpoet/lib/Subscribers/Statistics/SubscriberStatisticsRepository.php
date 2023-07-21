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
use MailPoetVendor\Carbon\Carbon;
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

  public function getStatisticsClickCount(SubscriberEntity $subscriber): int {
    $dateTime = (new Carbon())->subYear();
    return (int)$this->getStatisticsCountQuery(StatisticsClickEntity::class, $subscriber)
      ->andWhere('stats.createdAt > :dateTime')
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getStatisticsOpenCountQuery(SubscriberEntity $subscriber): QueryBuilder {
    $dateTime = (new Carbon())->subYear();
    return $this->getStatisticsCountQuery(StatisticsOpenEntity::class, $subscriber)
      ->join('stats.newsletter', 'newsletter')
      ->andWhere('(newsletter.sentAt > :dateTime OR newsletter.sentAt IS NULL)')
      ->andWhere('stats.createdAt > :dateTime')
      ->setParameter('dateTime', $dateTime);
  }

  public function getStatisticsOpenCount(SubscriberEntity $subscriber): int {
    return (int)$this->getStatisticsOpenCountQuery($subscriber)
      ->andWhere('(stats.userAgentType = :userAgentType)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getStatisticsMachineOpenCount(SubscriberEntity $subscriber): int {
    return (int)$this->getStatisticsOpenCountQuery($subscriber)
      ->andWhere('(stats.userAgentType = :userAgentType)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getTotalSentCount(SubscriberEntity $subscriber): int {
    $dateTime = (new Carbon())->subYear();
    return $this->getStatisticsCountQuery(StatisticsNewsletterEntity::class, $subscriber)
      ->andWhere('stats.sentAt > :dateTime')
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getStatisticsCountQuery(string $entityName, SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT stats.newsletter) as cnt')
      ->from($entityName, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber);
  }

  public function getWooCommerceRevenue(SubscriberEntity $subscriber) {
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
