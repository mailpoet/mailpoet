<?php

namespace MailPoet\Newsletter;

use DateTimeInterface;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<NewsletterEntity>
 */
class NewslettersRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  /**
   * @param string[] $types
   * @return NewsletterEntity[]
   */
  public function findActiveByTypes($types) {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->setParameter(':status', NewsletterEntity::STATUS_ACTIVE)
      ->andWhere('n.deletedAt is null')
      ->andWhere('n.type IN (:types)')
      ->setParameter('types', $types)
      ->orderBy('n.subject')
      ->getQuery()
      ->getResult();
  }

  public function getStandardNewsletterSentCount(DateTimeInterface $since): int {
    return (int)$this->doctrineRepository->createQueryBuilder('n')
      ->select('COUNT(n)')
      ->join('n.queues', 'q')
      ->join('q.task', 't')
      ->andWhere('n.type = :type')
      ->andWhere('n.status = :status')
      ->andWhere('t.status = :taskStatus')
      ->andWhere('t.processedAt >= :since')
      ->setParameter('type', NewsletterEntity::TYPE_STANDARD)
      ->setParameter('status', NewsletterEntity::STATUS_SENT)
      ->setParameter('taskStatus', ScheduledTaskEntity::STATUS_COMPLETED)
      ->setParameter('since', $since)
      ->getQuery()
      ->getSingleScalarResult() ?: 0;
  }

  public function getAnalytics(): array {
    // for automatic emails join 'event' newsletter option to further group the counts
    $eventOptionId = (int)$this->entityManager->createQueryBuilder()
      ->select('nof.id')
      ->from(NewsletterOptionFieldEntity::class, 'nof')
      ->andWhere('nof.newsletterType = :eventOptionFieldType')
      ->andWhere('nof.name = :eventOptionFieldName')
      ->setParameter('eventOptionFieldType', NewsletterEntity::TYPE_AUTOMATIC)
      ->setParameter('eventOptionFieldName', 'event')
      ->getQuery()
      ->getSingleScalarResult();

    $results = $this->doctrineRepository->createQueryBuilder('n')
      ->select('n.type, eventOption.value AS event, COUNT(n) AS cnt')
      ->leftJoin('n.options', 'eventOption', Join::WITH, "eventOption.optionField = :eventOptionId")
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.status IN (:statuses)')
      ->setParameter('eventOptionId', $eventOptionId)
      ->setParameter('statuses', [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_SENT])
      ->groupBy('n.type, eventOption.value')
      ->getQuery()
      ->getResult();

    $analyticsMap = [];
    foreach ($results as $result) {
      $type = $result['type'];
      if ($type === NewsletterEntity::TYPE_AUTOMATIC) {
        $analyticsMap[$type][$result['event'] ?? ''] = (int)$result['cnt'];
      } else {
        $analyticsMap[$type] = (int)$result['cnt'];
      }
    }

    return [
      'welcome_newsletters_count' => $analyticsMap[NewsletterEntity::TYPE_WELCOME] ?? 0,
      'notifications_count' => $analyticsMap[NewsletterEntity::TYPE_NOTIFICATION] ?? 0,
      'automatic_emails_count' => array_sum($analyticsMap[NewsletterEntity::TYPE_AUTOMATIC] ?? []),
      'sent_newsletters_count' => $analyticsMap[NewsletterEntity::TYPE_STANDARD] ?? 0,
      'sent_newsletters_3_months' => $this->getStandardNewsletterSentCount(Carbon::now()->subMonths(3)),
      'sent_newsletters_30_days' => $this->getStandardNewsletterSentCount(Carbon::now()->subDays(30)),
      'first_purchase_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][FirstPurchase::SLUG] ?? 0,
      'product_purchased_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][PurchasedProduct::SLUG] ?? 0,
      'product_purchased_in_category_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][PurchasedInCategory::SLUG] ?? 0,
      'abandoned_cart_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][AbandonedCart::SLUG] ?? 0,
    ];
  }
}
