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
use MailPoet\Settings\TrackingConfig;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WooCommerce\OrderAttributionRevenueReader;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<SubscriberEntity>
 */
class SubscriberStatisticsRepository extends Repository {
  public const ENGAGEMENT_SCORE_UNKNOWN = 'unknown';
  public const ENGAGEMENT_SCORE_DORMANT = 'dormant';
  public const ENGAGEMENT_SCORE_LOW = 'low';
  public const ENGAGEMENT_SCORE_GOOD = 'good';
  public const ENGAGEMENT_SCORE_EXCELLENT = 'excellent';

  public const MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE = 3;
  private const ENGAGEMENT_SCORE_LOW_MAX = 20;
  private const ENGAGEMENT_SCORE_GOOD_MAX = 50;

  /** @var WCHelper */
  private $wcHelper;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var OrderAttributionRevenueReader */
  private $orderAttributionRevenueReader;

  public function __construct(
    EntityManager $entityManager,
    WCHelper $wcHelper,
    TrackingConfig $trackingConfig,
    OrderAttributionRevenueReader $orderAttributionRevenueReader
  ) {
    parent::__construct($entityManager);
    $this->wcHelper = $wcHelper;
    $this->trackingConfig = $trackingConfig;
    $this->orderAttributionRevenueReader = $orderAttributionRevenueReader;
  }

  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  public function getStatistics(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null) {
    return new SubscriberStatistics(
      $this->getStatisticsClickCount($subscriber, $startTime),
      $this->getStatisticsOpenCount($subscriber, $startTime),
      $this->getStatisticsMachineOpenCount($subscriber, $startTime),
      $this->getTotalSentCount($subscriber, $startTime),
      $this->getWooCommerceRevenue($subscriber, $startTime)
    );
  }

  public function getEngagementScoreType(SubscriberEntity $subscriber): string {
    $scoreTypes = $this->getEngagementScoreTypes([$subscriber]);
    return $scoreTypes[(int)$subscriber->getId()] ?? self::ENGAGEMENT_SCORE_UNKNOWN;
  }

  /**
   * @param SubscriberEntity[] $subscribers
   * @return array<int, string>
   */
  public function getEngagementScoreTypes(array $subscribers): array {
    if (!$subscribers) {
      return [];
    }
    $yearAgo = new \DateTimeImmutable('-1 year');
    $lifetimeSentCounts = $this->getTotalSentCounts($subscribers);
    $recentSentCounts = $this->getTotalSentCounts($subscribers, $yearAgo);
    $scoreTypes = [];
    foreach ($subscribers as $subscriber) {
      $subscriberId = (int)$subscriber->getId();
      $scoreTypes[$subscriberId] = self::getEngagementScoreTypeFromData(
        $subscriber->getEngagementScore(),
        $lifetimeSentCounts[$subscriberId] ?? 0,
        $recentSentCounts[$subscriberId] ?? 0
      );
    }
    return $scoreTypes;
  }

  public static function getEngagementScoreTypeFromData(?float $score, int $lifetimeSentCount, int $recentSentCount): string {
    if ($score === null) {
      if (
        $lifetimeSentCount >= self::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
        && $recentSentCount < self::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
      ) {
        return self::ENGAGEMENT_SCORE_DORMANT;
      }
      return self::ENGAGEMENT_SCORE_UNKNOWN;
    }
    if ($score < self::ENGAGEMENT_SCORE_LOW_MAX) {
      return self::ENGAGEMENT_SCORE_LOW;
    }
    if ($score < self::ENGAGEMENT_SCORE_GOOD_MAX) {
      return self::ENGAGEMENT_SCORE_GOOD;
    }
    return self::ENGAGEMENT_SCORE_EXCELLENT;
  }

  public function getStatisticsClickCount(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): int {
    $queryBuilder = $this->getStatisticsCountQuery(StatisticsClickEntity::class, $subscriber);
    if ($startTime) {
      $this->applyDateConstraint($queryBuilder, $startTime);
    }
    return (int)$queryBuilder
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getStatisticsOpenCountQuery(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): QueryBuilder {
    $queryBuilder = $this->getStatisticsCountQuery(StatisticsOpenEntity::class, $subscriber);
    if ($startTime) {
      $this->applyDateConstraint($queryBuilder, $startTime);
    }
    return $queryBuilder;
  }

  public function getStatisticsOpenCount(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): int {
    return $this->getStatisticsOpenCounts([$subscriber], $startTime)[(int)$subscriber->getId()] ?? 0;
  }

  public function getStatisticsMachineOpenCount(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): int {
    return (int)$this->getStatisticsOpenCountQuery($subscriber, $startTime)
      ->andWhere('(stats.userAgentType = :userAgentType)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getTotalSentCount(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): int {
    $queryBuilder = $this->getStatisticsCountQuery(StatisticsNewsletterEntity::class, $subscriber);
    if ($startTime) {
      $queryBuilder
        ->andWhere('stats.sentAt >= :dateTime')
        ->setParameter('dateTime', $startTime);
    }
    return (int)$queryBuilder
      ->getQuery()
      ->getSingleScalarResult();
  }

  /**
   * @param SubscriberEntity[] $subscribers
   * @return array<int, int>
   */
  public function getTotalSentCounts(array $subscribers, ?\DateTimeInterface $startTime = null): array {
    if (!$subscribers) {
      return [];
    }
    $queryBuilder = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.subscriber) AS subscriber_id, COUNT(DISTINCT stats.newsletter) AS sent_count')
      ->from(StatisticsNewsletterEntity::class, 'stats')
      ->where('stats.subscriber IN (:subscribers)')
      ->groupBy('stats.subscriber')
      ->setParameter('subscribers', $subscribers);
    if ($startTime) {
      $queryBuilder
        ->andWhere('stats.sentAt >= :dateTime')
        ->setParameter('dateTime', $startTime);
    }
    $rows = $queryBuilder->getQuery()->getResult();
    $counts = [];
    foreach ($rows as $row) {
      $counts[(int)$row['subscriber_id']] = (int)$row['sent_count'];
    }
    return $counts;
  }

  /**
   * @param SubscriberEntity[] $subscribers
   * @return array<int, int>
   */
  public function getStatisticsOpenCounts(array $subscribers, ?\DateTimeInterface $startTime = null): array {
    if (!$subscribers) {
      return [];
    }
    $queryBuilder = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.subscriber) AS subscriber_id, COUNT(DISTINCT stats.newsletter) AS open_count')
      ->from(StatisticsOpenEntity::class, 'stats')
      ->where('stats.subscriber IN (:subscribers)')
      ->groupBy('stats.subscriber')
      ->setParameter('subscribers', $subscribers);
    if ($startTime) {
      $this->applyDateConstraint($queryBuilder, $startTime);
    }
    if ($this->trackingConfig->areOpensSeparated()) {
      $queryBuilder
        ->andWhere('stats.userAgentType = :userAgentType')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    }
    $rows = $queryBuilder->getQuery()->getResult();
    $counts = [];
    foreach ($rows as $row) {
      $counts[(int)$row['subscriber_id']] = (int)$row['open_count'];
    }
    return $counts;
  }

  public function getStatisticsCountQuery(string $entityName, SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT stats.newsletter) as cnt')
      ->from($entityName, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber);
  }

  public function getWooCommerceRevenue(SubscriberEntity $subscriber, ?\DateTimeInterface $startTime = null): ?WooCommerceRevenue {
    if (!$this->wcHelper->isWooCommerceActive()) {
      return null;
    }

    $revenueStatus = $this->wcHelper->getPurchaseStates();
    $currency = $this->wcHelper->getWoocommerceCurrency();
    $wooBackedRevenue = $this->orderAttributionRevenueReader->getSubscriberRevenue((int)$subscriber->getId(), $startTime);
    if (is_array($wooBackedRevenue)) {
      return new WooCommerceRevenue(
        $currency,
        (float)$wooBackedRevenue['total'],
        (int)$wooBackedRevenue['count'],
        $this->wcHelper
      );
    }

    $queryBuilder = $this->entityManager->createQueryBuilder()
      ->select('stats.orderPriceTotal')
      ->from(StatisticsWooCommercePurchaseEntity::class, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->andWhere('stats.orderCurrency = :currency')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('currency', $currency)
      ->andWhere('stats.status IN (:revenue_status)')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('currency', $currency)
      ->setParameter('revenue_status', $revenueStatus)
      ->groupBy('stats.orderId, stats.orderPriceTotal');
    if ($startTime) {
      $queryBuilder
        ->andWhere('stats.createdAt >= :dateTime')
        ->setParameter('dateTime', $startTime);
    }
    $purchases =
      $queryBuilder->getQuery()
        ->getResult();
    $sum = array_sum(array_column($purchases, 'orderPriceTotal'));
    return new WooCommerceRevenue(
      $currency,
      (float)$sum,
      count($purchases),
      $this->wcHelper
    );
  }

  private function applyDateConstraint(QueryBuilder $queryBuilder, \DateTimeInterface $startTime): QueryBuilder {
    $queryBuilder->join(StatisticsNewsletterEntity::class, 'sent_stats', 'WITH', 'stats.newsletter = sent_stats.newsletter AND stats.subscriber = sent_stats.subscriber AND sent_stats.sentAt >= :dateTime')
      ->setParameter('dateTime', $startTime);

    return $queryBuilder;
  }
}
