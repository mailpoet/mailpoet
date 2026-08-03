<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WooCommerce\OrderAttributionRevenueReader;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\UnexpectedResultException;

/**
 * @extends Repository<NewsletterEntity>
 */
class NewsletterStatisticsRepository extends Repository {
  /**
   * Emails that are sent again for every trigger instead of once as a campaign, so they
   * keep adding sending queues and scheduled tasks for as long as they stay active.
   */
  private const TYPES_SENT_REPEATEDLY = [
    NewsletterEntity::TYPE_WELCOME,
    NewsletterEntity::TYPE_AUTOMATIC,
    NewsletterEntity::TYPE_AUTOMATION,
    NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
    NewsletterEntity::TYPE_AUTOMATION_NOTIFICATION,
    NewsletterEntity::TYPE_RE_ENGAGEMENT,
  ];

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
    return NewsletterEntity::class;
  }

  public function getStatistics(NewsletterEntity $newsletter): NewsletterStatistics {
    $stats = new NewsletterStatistics(
      $this->getStatisticsClickCount($newsletter),
      $this->getStatisticsOpenCount($newsletter),
      $this->getStatisticsUnsubscribeCount($newsletter),
      $this->getStatisticsBounceCount($newsletter),
      $this->getTotalSentCount($newsletter),
      $this->getWooCommerceRevenue($newsletter)
    );
    $stats->setMachineOpenCount($this->getStatisticsMachineOpenCount($newsletter));
    return $stats;
  }

  /**
   * @param NewsletterEntity[] $newsletters
   * @return NewsletterStatistics[]
   */
  public function getBatchStatistics(
    array $newsletters,
    ?\DateTimeImmutable $from = null,
    ?\DateTimeImmutable $to = null,
    array $include = [
      'totals',
      StatisticsClickEntity::class,
      StatisticsOpenEntity::class,
      StatisticsUnsubscribeEntity::class,
      StatisticsBounceEntity::class,
      WooCommerceRevenue::class,
    ]
  ): array {

    $totalSentCounts = in_array('totals', $include, true) ? $this->getTotalSentCounts($newsletters, $from, $to) : [];
    $clickCounts = in_array(StatisticsClickEntity::class, $include, true) ? $this->getStatisticCounts(StatisticsClickEntity::class, $newsletters, $from, $to) : [];
    $openCounts = in_array(StatisticsOpenEntity::class, $include, true) ? $this->getStatisticCounts(StatisticsOpenEntity::class, $newsletters, $from, $to) : [];
    $unsubscribeCounts = in_array(StatisticsUnsubscribeEntity::class, $include, true) ? $this->getStatisticCounts(StatisticsUnsubscribeEntity::class, $newsletters, $from, $to) : [];
    $bounceCounts = in_array(StatisticsBounceEntity::class, $include, true) ? $this->getStatisticCounts(StatisticsBounceEntity::class, $newsletters, $from, $to) : [];
    $wooCommerceRevenues = in_array(WooCommerceRevenue::class, $include, true) ? $this->getWooCommerceRevenues($newsletters, $from, $to) : [];

    $statistics = [];
    foreach ($newsletters as $newsletter) {
      $id = $newsletter->getId();
      $statistics[$id] = new NewsletterStatistics(
        $clickCounts[$id] ?? 0,
        $openCounts[$id] ?? 0,
        $unsubscribeCounts[$id] ?? 0,
        $bounceCounts[$id] ?? 0,
        $totalSentCounts[$id] ?? 0,
        $wooCommerceRevenues[$id] ?? null
      );
    }
    return $statistics;
  }

  public function getTotalSentCount(NewsletterEntity $newsletter): int {
    $counts = $this->getTotalSentCounts([$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getStatisticsClickCount(NewsletterEntity $newsletter): int {
    $counts = $this->getStatisticCounts(StatisticsClickEntity::class, [$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getStatisticsOpenCount(NewsletterEntity $newsletter): int {
    $counts = $this->getStatisticCounts(StatisticsOpenEntity::class, [$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getStatisticsMachineOpenCount(NewsletterEntity $newsletter): int {
    $qb = $this->getStatisticsQuery(StatisticsOpenEntity::class, [$newsletter]);
    $result = $qb->andWhere('(stats.userAgentType = :userAgentType)')
      ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE)
      ->getQuery()
      ->getOneOrNullResult();

    if (empty($result)) return 0;
    return $result['cnt'] ?? 0;
  }

  /**
   * @param SubscriberEntity $subscriber
   * @param int|null $limit
   * @param int|null $offset
   * @return list<array{newsletter_id: mixed, newsletter_rendered_subject: string|null, opened_at: \DateTimeInterface|null, sent_at: \DateTimeInterface}>
   */
  public function getAllForSubscriber(
    SubscriberEntity $subscriber,
    ?int $limit = null,
    ?int $offset = null
  ): array {
    return $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(statistics.newsletter) AS newsletter_id')
      ->addSelect('opens.createdAt AS opened_at')
      ->addSelect('queue.newsletterRenderedSubject AS newsletter_rendered_subject')
      ->addSelect('statistics.sentAt AS sent_at')
      ->from(StatisticsNewsletterEntity::class, 'statistics')
      ->join(SendingQueueEntity::class, 'queue', Join::WITH, 'statistics.queue = queue')
      ->leftJoin(
        StatisticsOpenEntity::class,
        'opens',
        Join::WITH,
        'statistics.newsletter = opens.newsletter AND statistics.subscriber = opens.subscriber'
      )
      ->where('statistics.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->addOrderBy('newsletter_id')
      ->setMaxResults($limit)
      ->setFirstResult($offset)
      ->getQuery()
      ->getResult();
  }

  public function getStatisticsUnsubscribeCount(NewsletterEntity $newsletter): int {
    $counts = $this->getStatisticCounts(StatisticsUnsubscribeEntity::class, [$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getStatisticsBounceCount(NewsletterEntity $newsletter): int {
    $counts = $this->getStatisticCounts(StatisticsBounceEntity::class, [$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getWooCommerceRevenue(NewsletterEntity $newsletter) {
    $revenues = $this->getWooCommerceRevenues([$newsletter]);
    return $revenues[$newsletter->getId()] ?? null;
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getChildrenCount(NewsletterEntity $newsletter) {
    try {
      return (int)$this->entityManager
        ->createQueryBuilder()
        ->select('COUNT(n.id) as cnt')
        ->from(NewsletterEntity::class, 'n')
        ->where('n.parent = :newsletter')
        ->setParameter('newsletter', $newsletter)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultException $e) {
      return 0;
    }
  }

  private function getTotalSentCounts(array $newsletters, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array {
    $sentRepeatedly = [];
    $sentAsCampaign = [];
    foreach ($newsletters as $newsletter) {
      if (in_array($newsletter->getType(), self::TYPES_SENT_REPEATEDLY, true)) {
        $sentRepeatedly[] = $newsletter;
      } else {
        $sentAsCampaign[] = $newsletter;
      }
    }

    // no key collisions, a newsletter belongs to exactly one group
    return $this->getQueuedSentCounts($sentAsCampaign, $from, $to)
      + $this->getRecordedSentCounts($sentRepeatedly, $from, $to);
  }

  /**
   * Counts sends from the sending queues, which hold one row per sending run.
   */
  private function getQueuedSentCounts(array $newsletters, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array {
    if (!$newsletters) {
      return [];
    }

    $query = $this->doctrineRepository
      ->createQueryBuilder('n')
      ->select('n.id, SUM(q.countProcessed) AS cnt')
      ->join('n.queues', 'q')
      ->join('q.task', 't')
      ->where('t.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
      ->andWhere('q.newsletter IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->groupBy('n.id');

    if ($from && $to) {
      $query->andWhere('q.createdAt BETWEEN :from AND :to')
        ->setParameter('from', $from)
        ->setParameter('to', $to);
    } elseif ($from && $to === null) {
      $query->andWhere('q.createdAt >= :from')
        ->setParameter('from', $from);
    } elseif ($from === null && $to) {
      $query->andWhere('q.createdAt <= :to')
        ->setParameter('to', $to);
    }

    $results = $query->getQuery()
      ->getResult();

    $counts = [];
    foreach ($results ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  /**
   * Counts sends from the sending statistics, which hold one row per email actually sent.
   *
   * Counting a repeatedly sent email through its queues instead would make the total depend
   * on a chain of rows that grows for the lifetime of the email, while the opens and clicks
   * measured against that total are only ever removed along with the newsletter itself. The
   * sending statistics share that same lifecycle, so both sides of a rate stay consistent
   * even where the queue chain has lost rows.
   */
  private function getRecordedSentCounts(array $newsletters, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array {
    if (!$newsletters) {
      return [];
    }

    $query = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, COUNT(stats.id) AS cnt')
      ->from(StatisticsNewsletterEntity::class, 'stats')
      ->where('stats.newsletter IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->groupBy('stats.newsletter');

    if ($from && $to) {
      $query->andWhere('stats.sentAt BETWEEN :from AND :to')
        ->setParameter('from', $from)
        ->setParameter('to', $to);
    } elseif ($from && $to === null) {
      $query->andWhere('stats.sentAt >= :from')
        ->setParameter('from', $from);
    } elseif ($from === null && $to) {
      $query->andWhere('stats.sentAt <= :to')
        ->setParameter('to', $to);
    }

    $results = $query->getQuery()
      ->getResult();

    $counts = [];
    foreach ($results ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  private function getStatisticCounts(string $statisticsEntityName, array $newsletters, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array {
    $qb = $this->getStatisticsQuery($statisticsEntityName, $newsletters);
    if (
      $statisticsEntityName === StatisticsClickEntity::class
      || ($statisticsEntityName === StatisticsOpenEntity::class && $this->trackingConfig->areOpensSeparated())
    ) {
      $qb->andWhere('(stats.userAgentType = :userAgentType) OR (stats.userAgentType IS NULL)')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    }
    if ($from && $to) {
      $qb->andWhere('stats.createdAt BETWEEN :from AND :to')
        ->setParameter('from', $from)
        ->setParameter('to', $to);
    } elseif ($from && $to === null) {
      $qb->andWhere('stats.createdAt >= :from')
        ->setParameter('from', $from);
    } elseif ($from === null && $to) {
      $qb->andWhere('stats.createdAt <= :to')
        ->setParameter('to', $to);
    }

    $results = $qb
      ->getQuery()
      ->getResult();

    $counts = [];
    foreach ($results ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  private function getStatisticsQuery(string $statisticsEntityName, array $newsletters): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, COUNT(DISTINCT stats.subscriber) as cnt')
      ->from($statisticsEntityName, 'stats')
      ->where('stats.newsletter IN (:newsletters)')
      ->groupBy('stats.newsletter')
      ->setParameter('newsletters', $newsletters);
  }

  private function getWooCommerceRevenues(array $newsletters, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null) {
    if (!$this->wcHelper->isWooCommerceActive()) {
      return null;
    }

    $newsletterIds = array_map(function(NewsletterEntity $newsletter): int {
      return (int)$newsletter->getId();
    }, $newsletters);
    $revenueStatus = $this->wcHelper->getPurchaseStates();
    $currency = $this->wcHelper->getWoocommerceCurrency();
    $wooBackedRevenues = $this->orderAttributionRevenueReader->getNewsletterRevenues($newsletterIds, $from, $to);
    if (is_array($wooBackedRevenues)) {
      $revenues = [];
      foreach ($wooBackedRevenues as $newsletterId => $result) {
        $revenues[(int)$newsletterId] = new WooCommerceRevenue(
          $currency,
          (float)$result['total'],
          (int)$result['count'],
          $this->wcHelper
        );
      }
      return $revenues;
    }

    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, SUM(stats.orderPriceTotal) AS total, COUNT(stats.id) AS cnt')
      ->from(StatisticsWooCommercePurchaseEntity::class, 'stats')
      ->where('stats.newsletter IN (:newsletters)')
      ->andWhere('stats.orderCurrency = :currency')
      ->andWhere('stats.status IN (:revenue_status)')
      ->setParameter('newsletters', $newsletters)
      ->setParameter('currency', $currency)
      ->setParameter('revenue_status', $revenueStatus)
      ->groupBy('stats.newsletter');

    if ($from && $to) {
      $query->andWhere('stats.createdAt BETWEEN :from AND :to')
        ->setParameter('from', $from)
        ->setParameter('to', $to);
    } elseif ($from && $to === null) {
      $query->andWhere('stats.createdAt >= :from')
        ->setParameter('from', $from);
    } elseif ($from === null && $to) {
      $query->andWhere('stats.createdAt <= :to')
        ->setParameter('to', $to);
    }

    $results = $query->getQuery()
      ->getResult();

    $revenues = [];
    foreach ($results ?: [] as $result) {
      $revenues[(int)$result['id']] = new WooCommerceRevenue(
        $currency,
        (float)$result['total'],
        (int)$result['cnt'],
        $this->wcHelper
      );
    }
    return $revenues;
  }
}
