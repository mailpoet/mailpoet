<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<StatisticsOpenEntity>
 */
class StatisticsOpensRepository extends Repository {
  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    EntityManager $entityManager,
    TrackingConfig $trackingConfig
  ) {
    parent::__construct($entityManager);
    $this->entityManager = $entityManager;
    $this->trackingConfig = $trackingConfig;
  }

  protected function getEntityClassName(): string {
    return StatisticsOpenEntity::class;
  }

  public function recalculateSubscriberScore(SubscriberEntity $subscriber): void {
    $subscriberId = $subscriber->getId();
    if (!$subscriberId) {
      return;
    }
    $this->recalculateSubscribersScore([$subscriberId]);
  }

  /**
   * Recalculates and persists engagement scores in a single bulk UPDATE. Runs entirely
   * in SQL so large batches avoid entity hydration, validation, and per-entity flushes.
   * In-memory SubscriberEntity instances are not refreshed and keep stale score fields.
   *
   * @param int[] $subscriberIds
   */
  public function recalculateSubscribersScore(array $subscriberIds): void {
    if (!$subscriberIds) {
      return;
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $sentStatsTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $openStatsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $humanOpensCondition = $this->trackingConfig->areOpensSeparated() ? ' AND so.user_agent_type = :userAgentType' : '';

    $sql = "
      UPDATE {$subscribersTable} s
      LEFT JOIN (
        SELECT subscriber_id, COUNT(DISTINCT newsletter_id) AS sent_count
        FROM {$sentStatsTable}
        WHERE subscriber_id IN (:ids) AND sent_at >= :yearAgo
        GROUP BY subscriber_id
      ) sent ON sent.subscriber_id = s.id
      LEFT JOIN (
        SELECT so.subscriber_id, COUNT(DISTINCT so.newsletter_id) AS open_count
        FROM {$openStatsTable} so
        JOIN {$sentStatsTable} sn
          ON sn.newsletter_id = so.newsletter_id
          AND sn.subscriber_id = so.subscriber_id
          AND sn.sent_at >= :yearAgo
        WHERE so.subscriber_id IN (:ids){$humanOpensCondition}
        GROUP BY so.subscriber_id
      ) opens ON opens.subscriber_id = s.id
      SET s.engagement_score = CASE
            WHEN COALESCE(sent.sent_count, 0) < :minSentCount THEN NULL
            ELSE COALESCE(opens.open_count, 0) / sent.sent_count * 100
          END,
          s.engagement_score_updated_at = :now
      WHERE s.id IN (:ids)
    ";

    $parameters = [
      'ids' => array_map('intval', $subscriberIds),
      'yearAgo' => (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'),
      'minSentCount' => SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE,
      'now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    ];
    if ($humanOpensCondition) {
      $parameters['userAgentType'] = UserAgentEntity::USER_AGENT_TYPE_HUMAN;
    }
    $this->entityManager->getConnection()->executeStatement(
      $sql,
      $parameters,
      ['ids' => ArrayParameterType::INTEGER]
    );
  }

  public function resetSubscribersScoreCalculation() {
    $this->entityManager->createQueryBuilder()->update(SubscriberEntity::class, 's')
      ->set('s.engagementScoreUpdatedAt', ':updatedAt')
      ->setParameter('updatedAt', null)
      ->getQuery()->execute();
  }

  public function recalculateSegmentScore(SegmentEntity $segment): void {
    $segment->setAverageEngagementScoreUpdatedAt(new \DateTimeImmutable());
    $avgScore = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('avg(subscriber.engagementScore)')
      ->from(SubscriberEntity::class, 'subscriber')
      ->join('subscriber.subscriberSegments', 'subscriberSegments')
      ->where('subscriberSegments.segment = :segment')
      ->andWhere('subscriber.status = :subscribed')
      ->andWhere('subscriber.deletedAt IS NULL')
      ->andWhere('subscriberSegments.status = :subscribed')
      ->setParameter('segment', $segment)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()
      ->getSingleScalarResult();
    $segment->setAverageEngagementScore($avgScore === null ? $avgScore : (float)$avgScore);
    $this->entityManager->flush();
  }

  public function resetSegmentsScoreCalculation(): void {
    $this->entityManager->createQueryBuilder()->update(SegmentEntity::class, 's')
      ->set('s.averageEngagementScoreUpdatedAt', ':updatedAt')
      ->setParameter('updatedAt', null)
      ->getQuery()->execute();
  }

  public function getAllForSubscriber(SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('opens.id id, queue.newsletterRenderedSubject, opens.createdAt, userAgent.userAgent')
      ->from(StatisticsOpenEntity::class, 'opens')
      ->join('opens.queue', 'queue')
      ->leftJoin('opens.userAgent', 'userAgent')
      ->where('opens.subscriber = :subscriber')
      ->orderBy('queue.newsletterRenderedSubject')
      ->setParameter('subscriber', $subscriber->getId());
  }

  /** @param int[] $ids */
  public function deleteByNewsletterIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(StatisticsOpenEntity::class, 's')
      ->where('s.newsletter IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (StatisticsOpenEntity $entity) use ($ids) {
      $newsletter = $entity->getNewsletter();
      return $newsletter && in_array($newsletter->getId(), $ids, true);
    });
  }
}
