<?php

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\UnexpectedResultException;

/**
 * @extends Repository<NewsletterEntity>
 */
class NewsletterStatisticsRepository extends Repository {

  /** @var WCHelper */
  private $wcHelper;

  public function __construct(EntityManager $entityManager, WCHelper $wcHelper) {
    parent::__construct($entityManager);
    $this->wcHelper = $wcHelper;
  }

  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  public function getStatistics(NewsletterEntity $newsletter): NewsletterStatistics {
    return new NewsletterStatistics(
      $this->getStatisticsClickCount($newsletter),
      $this->getStatisticsOpenCount($newsletter),
      $this->getStatisticsUnsubscribeCount($newsletter),
      $this->getTotalSentCount($newsletter),
      $this->getWooCommerceRevenue($newsletter)
    );
  }

  /**
   * @param NewsletterEntity[] $newsletters
   * @return NewsletterStatistics[]
   */
  public function getBatchStatistics(array $newsletters): array {
    $totalSentCounts = $this->getTotalSentCounts($newsletters);
    $clickCounts = $this->getStatisticCounts(StatisticsClickEntity::class, $newsletters);
    $openCounts = $this->getStatisticCounts(StatisticsOpenEntity::class, $newsletters);
    $unsubscribeCounts = $this->getStatisticCounts(StatisticsUnsubscribeEntity::class, $newsletters);
    $wooCommerceRevenues = $this->getWooCommerceRevenues($newsletters);

    $statistics = [];
    foreach ($newsletters as $newsletter) {
      $id = $newsletter->getId();
      $statistics[$id] = new NewsletterStatistics(
        $clickCounts[$id] ?? 0,
        $openCounts[$id] ?? 0,
        $unsubscribeCounts[$id] ?? 0,
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

  public function getStatisticsUnsubscribeCount(NewsletterEntity $newsletter): int {
    $counts = $this->getStatisticCounts(StatisticsUnsubscribeEntity::class, [$newsletter]);
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

  private function getTotalSentCounts(array $newsletters): array {
    $results = $this->doctrineRepository
      ->createQueryBuilder('n')
      ->select('n.id, SUM(q.countProcessed) AS cnt')
      ->join('n.queues', 'q')
      ->join('q.task', 't')
      ->where('t.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
      ->andWhere('q.newsletter IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->groupBy('n.id')
      ->getQuery()
      ->getResult();

    $counts = [];
    foreach ($results ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  private function getStatisticCounts(string $statisticsEntityName, array $newsletters): array {
    $results = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, COUNT(DISTINCT stats.subscriber) as cnt')
      ->from($statisticsEntityName, 'stats')
      ->where('stats.newsletter IN (:newsletters)')
      ->groupBy('stats.newsletter')
      ->setParameter('newsletters', $newsletters)
      ->getQuery()
      ->getResult();

    $counts = [];
    foreach ($results ?: [] as $result) {
      $counts[(int)$result['id']] = (int)$result['cnt'];
    }
    return $counts;
  }

  private function getWooCommerceRevenues(array $newsletters) {
    if (!$this->wcHelper->isWooCommerceActive()) {
      return null;
    }

    $currency = $this->wcHelper->getWoocommerceCurrency();
    $results = $this->entityManager
      ->createQueryBuilder()
      ->select('IDENTITY(stats.newsletter) AS id, SUM(stats.orderPriceTotal) AS total, COUNT(stats.id) AS cnt')
      ->from(StatisticsWooCommercePurchaseEntity::class, 'stats')
      ->where('stats.newsletter IN (:newsletters)')
      ->andWhere('stats.orderCurrency = :currency')
      ->setParameter('newsletters', $newsletters)
      ->setParameter('currency', $currency)
      ->groupBy('stats.newsletter')
      ->getQuery()
      ->getResult();

    $revenues = [];
    foreach ($results ?: [] as $result) {
      $revenues[(int)$result['id']] = new NewsletterWooCommerceRevenue(
        $currency,
        (float)$result['total'],
        (int)$result['cnt'],
        $this->wcHelper
      );
    }
    return $revenues;
  }
}
