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
use MailPoet\Subscribers\TrackingConsentController;
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

  /** @var TrackingConsentController */
  private $trackingConsentController;

  /**
   * sending_queues.meta key holding the untracked-recipient count for a
   * completed queue: ['count' => int, 'unknownTracked' => bool]. Filled lazily
   * on first read; the bool is the strict-mode flag it was computed under, so a
   * value from the other mode is recomputed instead of reused.
   */
  public const META_NOT_TRACKED = 'notTracked';

  public function __construct(
    EntityManager $entityManager,
    WCHelper $wcHelper,
    TrackingConfig $trackingConfig,
    OrderAttributionRevenueReader $orderAttributionRevenueReader,
    TrackingConsentController $trackingConsentController
  ) {
    parent::__construct($entityManager);
    $this->wcHelper = $wcHelper;
    $this->trackingConfig = $trackingConfig;
    $this->orderAttributionRevenueReader = $orderAttributionRevenueReader;
    $this->trackingConsentController = $trackingConsentController;
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
    $stats->setNotTrackedCount($this->getNotTrackedCount($newsletter));
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

    $includeTotals = in_array('totals', $include, true);
    $totalSentCounts = $includeTotals ? $this->getTotalSentCounts($newsletters, $from, $to) : [];
    // Tied to 'totals' rather than its own include member: trackedSent is
    // totalSent minus this, so a caller with one but not the other would be
    // told every recipient was tracked.
    $notTrackedCounts = $includeTotals ? $this->getNotTrackedCounts($newsletters, $from, $to) : [];
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
      $statistics[$id]->setNotTrackedCount($notTrackedCounts[$id] ?? 0);
    }
    return $statistics;
  }

  public function getTotalSentCount(NewsletterEntity $newsletter): int {
    $counts = $this->getTotalSentCounts([$newsletter]);
    return $counts[$newsletter->getId()] ?? 0;
  }

  public function getNotTrackedCount(NewsletterEntity $newsletter): int {
    $counts = $this->getNotTrackedCounts([$newsletter]);
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

  /**
   * Recipients we were not allowed to measure, per newsletter, read from
   * current consent plus WHEN it changed. No stored flag on the sent rows:
   * tracking_consent_updated_at is the *last* change, so "denied now and last
   * changed before we sent" means "denied when we sent". An opt-out after the
   * send leaves that send alone, so a recorded open can never lose its recipient
   * from the denominator (no >100%). A deleted recipient does not join and stays
   * in the denominator, as today. In strict mode (ask_all) recipients we never
   * asked are untracked too.
   *
   * Split the same way as getTotalSentCounts(), and it has to stay that way:
   * trackedSent is totalSent minus this, so each group must be counted over the
   * very population its total came from. Counting a repeatedly sent email over
   * completed queues while its total came from the sending statistics would
   * subtract two different audiences and could push a rate past 100%.
   *
   * @param NewsletterEntity[] $newsletters
   * @return array<int, int>
   */
  private function getNotTrackedCounts(array $newsletters, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array {
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
    return $this->getQueuedNotTrackedCounts($sentAsCampaign, $from, $to)
      + $this->getRecordedNotTrackedCounts($sentRepeatedly, $from, $to);
  }

  /**
   * The campaign side, matching getQueuedSentCounts(): completed tasks, same
   * q.createdAt window.
   *
   * Cached per completed queue in sending_queues.meta on first read: a completed
   * queue's count cannot change (nobody can opt out "before" a send that already
   * happened), so the live query runs once per queue and the listing then costs
   * the same as the total-sent query. The cache carries the strict-mode flag it
   * was computed under and is recomputed when the site's Subscriber choice moves
   * between ask_new and ask_all.
   *
   * @param NewsletterEntity[] $newsletters
   * @return array<int, int>
   */
  private function getQueuedNotTrackedCounts(array $newsletters, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array {
    if (!$newsletters) {
      return [];
    }

    $queues = $this->getCompletedQueues($newsletters, $from, $to);
    if (!$queues) {
      return [];
    }
    $unknownTracked = $this->trackingConsentController->shouldTrackUnknownConsent();

    $counts = [];
    $uncached = [];
    foreach ($queues as $queue) {
      $cached = $queue['meta'][self::META_NOT_TRACKED] ?? null;
      if (
        is_array($cached)
        && array_key_exists('count', $cached)
        && ($cached['unknownTracked'] ?? null) === $unknownTracked
      ) {
        $counts[$queue['newsletterId']] = ($counts[$queue['newsletterId']] ?? 0) + (int)$cached['count'];
      } else {
        $uncached[] = $queue;
      }
    }

    if ($uncached) {
      $fresh = $this->queryNotTrackedCountsPerQueue($uncached, $unknownTracked);
      foreach ($uncached as $queue) {
        $count = $fresh[$queue['id']] ?? 0;
        $counts[$queue['newsletterId']] = ($counts[$queue['newsletterId']] ?? 0) + $count;
        $this->cacheNotTrackedCount($queue['id'], $queue['meta'], $count, $unknownTracked);
      }
    }
    return $counts;
  }

  /**
   * The repeatedly-sent side, matching getRecordedSentCounts(): every sending
   * statistics row for the email, same stats.sentAt window, no task-status
   * filter — exactly the rows that total counted.
   *
   * Deliberately not cached. These emails keep sending for as long as they stay
   * active, so the count is not final the way a completed campaign's is, and a
   * value frozen on first read would drift below the total it is subtracted from.
   *
   * @param NewsletterEntity[] $newsletters
   * @return array<int, int>
   */
  private function getRecordedNotTrackedCounts(array $newsletters, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array {
    if (!$newsletters) {
      return [];
    }

    $query = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, COUNT(stats.id) AS cnt')
      ->from(StatisticsNewsletterEntity::class, 'stats')
      ->join('stats.subscriber', 's')
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

    $query->andWhere($this->buildUntrackedPredicate($query, $this->trackingConsentController->shouldTrackUnknownConsent()));

    $counts = [];
    foreach ($query->getQuery()->getResult() ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  /**
   * The queues getQueuedSentCounts() sums: completed tasks, same q.createdAt window.
   * Arrays, not entities, on purpose: nothing here should end up in the unit of
   * work, and the cache write below must not mark a queue dirty.
   *
   * @param NewsletterEntity[] $newsletters
   * @return array<int, array{id: int, newsletterId: int, meta: array|null}>
   */
  private function getCompletedQueues(array $newsletters, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('q.id AS id, IDENTITY(q.newsletter) AS newsletterId, q.meta AS meta')
      ->from(SendingQueueEntity::class, 'q')
      ->join('q.task', 't')
      ->where('t.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
      ->andWhere('q.newsletter IN (:newsletters)')
      ->setParameter('newsletters', $newsletters);

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

    $queues = [];
    foreach ($query->getQuery()->getArrayResult() as $row) {
      $meta = $row['meta'];
      if (is_string($meta)) { // scalar hydration may hand the JSON back undecoded
        $meta = json_decode($meta, true);
      }
      $queues[] = [
        'id' => (int)$row['id'],
        'newsletterId' => (int)$row['newsletterId'],
        'meta' => is_array($meta) ? $meta : null,
      ];
    }
    return $queues;
  }

  /**
   * The live query, per queue, for the queues that have no usable cache.
   *
   * @param array<int, array{id: int, newsletterId: int, meta: array|null}> $queues
   * @return array<int, int> queue id => untracked count (queues with none are absent)
   */
  private function queryNotTrackedCountsPerQueue(array $queues, bool $unknownTracked): array {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('IDENTITY(stats.queue) AS id, COUNT(stats.id) AS cnt')
      ->from(StatisticsNewsletterEntity::class, 'stats')
      ->join('stats.subscriber', 's')
      ->where('stats.newsletter IN (:newsletterIds)')
      ->setParameter('newsletterIds', array_values(array_unique(array_column($queues, 'newsletterId'))))
      ->andWhere('stats.queue IN (:queueIds)')
      ->setParameter('queueIds', array_column($queues, 'id'))
      ->groupBy('stats.queue');

    $query->andWhere($this->buildUntrackedPredicate($query, $unknownTracked));

    $counts = [];
    foreach ($query->getQuery()->getResult() ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  /**
   * One definition of "we were not allowed to measure this recipient", shared by
   * both counting paths so they can never drift apart. Expects the sending
   * statistics aliased as `stats` and the joined subscriber as `s`, and sets its
   * own parameters on the query it is given.
   */
  private function buildUntrackedPredicate(QueryBuilder $query, bool $unknownTracked): string {
    $untracked = '(s.trackingConsent = :denied AND s.trackingConsentUpdatedAt <= stats.sentAt)';
    $query->setParameter('denied', SubscriberEntity::TRACKING_CONSENT_DENIED);
    if (!$unknownTracked) {
      $untracked .= ' OR s.trackingConsent = :unknown';
      $query->setParameter('unknown', SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    }
    return "($untracked)";
  }

  /**
   * Store the count on the queue's meta, merged with whatever is there. A raw
   * UPDATE on purpose: it must not go through the unit of work (no flush of
   * unrelated pending changes from a read path, no updated_at bump — updated_at
   * is shown in the listing). Only completed queues reach here, and the only
   * other writers of meta (saveCampaignId, saveFilterSegmentMeta) run while a
   * queue is still sending, so there is nothing to race with; two readers
   * caching the same queue write the same value.
   *
   * `updated_at = updated_at` is not a no-op: the column is
   * `ON UPDATE current_timestamp()`, so without naming it here MySQL would
   * restamp it and the listing would show every campaign as just-touched.
   */
  private function cacheNotTrackedCount(int $queueId, ?array $meta, int $count, bool $unknownTracked): void {
    $meta = $meta ?? [];
    $meta[self::META_NOT_TRACKED] = ['count' => $count, 'unknownTracked' => $unknownTracked];
    $table = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE `{$table}` SET meta = ?, updated_at = updated_at WHERE id = ?",
      [json_encode($meta), $queueId]
    );
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
