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

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getTotalSentCount(NewsletterEntity $newsletter) {
    try {
      return (int)$this->doctrineRepository
        ->createQueryBuilder('n')
        ->join('n.queues', 'q')
        ->join('q.task', 't')
        ->select('SUM(q.countProcessed)')
        ->where('t.status = :status')
        ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
        ->andWhere('q.newsletter = :newsletter')
        ->setParameter('newsletter', $newsletter)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultException $e) {
      return 0;
    }
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return NewsletterStatistics
   */
  public function getStatistics(NewsletterEntity $newsletter) {
    return new NewsletterStatistics(
      $this->getStatisticsClickCount($newsletter),
      $this->getStatisticsOpenCount($newsletter),
      $this->getStatisticsUnsubscribeCount($newsletter),
      $this->getTotalSentCount($newsletter),
      $this->getWooCommerceRevenue($newsletter)
    );
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsClickCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsClickEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsOpenCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsOpenEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsUnsubscribeCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsUnsubscribeEntity::class);
  }

  public function getWooCommerceRevenue(NewsletterEntity $newsletter) {
    if (!$this->wcHelper->isWooCommerceActive()) {
      return null;
    }
    try {
      $currency = $this->wcHelper->getWoocommerceCurrency();
      list($data) = $this->entityManager
        ->createQueryBuilder()
        ->select('SUM(stats.orderPriceTotal) AS total, COUNT(stats.id) AS cnt')
        ->from(StatisticsWooCommercePurchaseEntity::class, 'stats')
        ->where('stats.newsletter = :newsletter')
        ->andWhere('stats.orderCurrency = :currency')
        ->setParameter('newsletter', $newsletter)
        ->setParameter('currency', $currency)
        ->getQuery()
        ->getResult();
      $value = (float)$data['total'];
      $count = (int)$data['cnt'];
      return new NewsletterWooCommerceRevenue($currency, $value, $count, $this->wcHelper);
    } catch (UnexpectedResultException $e) {
      return null;
    }
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

  private function getStatisticsCount(NewsletterEntity $newsletter, $statisticsEntityName) {
    try {
      $qb = $this->entityManager
        ->createQueryBuilder();
      return $qb->select('COUNT(DISTINCT stats.subscriberId) as cnt')
        ->from($statisticsEntityName, 'stats')
        ->where('stats.newsletter = :newsletter')
        ->setParameter('newsletter', $newsletter)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultException $e) {
      return 0;
    }
  }
}
