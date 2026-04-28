<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;

/**
 * @extends Repository<StatisticsUnsubscribeEntity>
 */
class StatisticsUnsubscribesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsUnsubscribeEntity::class;
  }

  public function getTotalForMonths(int $forMonths): int {
    $from = (new Carbon())->subMonths($forMonths);
    $count = $this->entityManager->createQueryBuilder()
      ->select('count(stats.id)')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.createdAt >= :dateTime')
      ->setParameter('dateTime', $from)
      ->getQuery()
      ->getSingleScalarResult();

    return intval($count);
  }

  public function getCountPerMethodForMonths(int $forMonths): array {
    $from = (new Carbon())->subMonths($forMonths);
    return $this->entityManager->createQueryBuilder()
      ->select('count(stats.id) as count, stats.method as method')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.createdAt >= :dateTime')
      ->groupBy('stats.method')
      ->setParameter('dateTime', $from)
      ->getQuery()
      ->getResult();
  }

  public function findOneBySubscriberAndQueue(SubscriberEntity $subscriber, SendingQueueEntity $queue, NewsletterEntity $newsletter): ?StatisticsUnsubscribeEntity {
    $statistics = $this->findOneBy([
      'queue' => $queue,
      'newsletter' => $newsletter,
      'subscriber' => $subscriber,
    ]);
    return $statistics instanceof StatisticsUnsubscribeEntity ? $statistics : null;
  }

  public function findLatestForSubscriber(SubscriberEntity $subscriber): ?StatisticsUnsubscribeEntity {
    $result = $this->entityManager->createQueryBuilder()
      ->select('stats')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->orderBy('stats.createdAt', 'DESC')
      ->addOrderBy('stats.id', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    return $result instanceof StatisticsUnsubscribeEntity ? $result : null;
  }

  public function getReasonCountsForNewsletter(NewsletterEntity $newsletter): array {
    $reasonCounts = $this->entityManager->createQueryBuilder()
      ->select('stats.reason as reason, count(stats.id) as count')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.newsletter = :newsletter')
      ->andWhere('stats.reason IS NOT NULL')
      ->groupBy('stats.reason')
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getArrayResult();

    $unspecifiedCount = $this->entityManager->createQueryBuilder()
      ->select('count(stats.id)')
      ->from(StatisticsUnsubscribeEntity::class, 'stats')
      ->andWhere('stats.newsletter = :newsletter')
      ->andWhere('stats.reason IS NULL')
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getSingleScalarResult();

    if ((int)$unspecifiedCount > 0) {
      $reasonCounts[] = [
        'reason' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'count' => $unspecifiedCount,
      ];
    }

    return $reasonCounts;
  }
}
