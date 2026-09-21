<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<StatisticsOpenEntity>
 */
class StatisticsOpensRepository extends Repository {
  /** @var TrackingConfig */
  private $trackingConfig;

  private TrackingConsentController $trackingConsentController;

  private bool $missingTrackingColumnLogged = false;

  public function __construct(
    EntityManager $entityManager,
    TrackingConfig $trackingConfig,
    TrackingConsentController $trackingConsentController
  ) {
    parent::__construct($entityManager);
    $this->entityManager = $entityManager;
    $this->trackingConfig = $trackingConfig;
    $this->trackingConsentController = $trackingConsentController;
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
    // The UPDATE ... LEFT JOIN below is not supported by the SQLite integration used in
    // WordPress Playground. Scores stay unset there and the listing reports them as
    // unknown; the sweep worker no-ops for the same reason (see SubscribersEngagementScore).
    // Without this guard the tracking endpoint throws, which also breaks the click redirect.
    if (Connection::isSQLite()) {
      return;
    }

    if (!$subscriberIds) {
      return;
    }

    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->runScoreUpdate($subscriberIds, true);
    } catch (InvalidFieldNameException $e) {
      // The column may not exist yet during a plugin update. Count every send,
      // which is what the score did before, until the migration runs.
      $this->logMissingTrackingColumn($e);
      $this->runScoreUpdate($subscriberIds, false);
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }

  /**
   * @param int[] $subscriberIds
   */
  private function runScoreUpdate(array $subscriberIds, bool $onlyTrackedSends): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $sentStatsTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $openStatsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $humanOpensCondition = $this->trackingConfig->areOpensSeparated() ? ' AND so.user_agent_type = :userAgentType' : '';
    $trackedSentCondition = $onlyTrackedSends ? ' AND sent_with_tracking = 1' : '';
    $trackedOpenCondition = $onlyTrackedSends ? ' AND sn.sent_with_tracking = 1' : '';
    // Sends before sent_with_tracking existed cannot tell whether a never-asked
    // subscriber got the pixel, so on sites asking everyone they get no score.
    $neverAskedCondition = $this->trackingConsentController->shouldTrackUnknownConsent()
      ? ''
      : "\n            WHEN s.tracking_consent = :unknownConsent THEN NULL";

    $sql = "
      UPDATE {$subscribersTable} s
      LEFT JOIN (
        SELECT subscriber_id, COUNT(DISTINCT newsletter_id) AS sent_count
        FROM {$sentStatsTable}
        WHERE subscriber_id IN (:ids) AND sent_at >= :yearAgo{$trackedSentCondition}
        GROUP BY subscriber_id
      ) sent ON sent.subscriber_id = s.id
      LEFT JOIN (
        SELECT so.subscriber_id, COUNT(DISTINCT so.newsletter_id) AS open_count
        FROM {$openStatsTable} so
        JOIN {$sentStatsTable} sn
          ON sn.newsletter_id = so.newsletter_id
          AND sn.subscriber_id = so.subscriber_id
          AND sn.sent_at >= :yearAgo{$trackedOpenCondition}
        WHERE so.subscriber_id IN (:ids){$humanOpensCondition}
        GROUP BY so.subscriber_id
      ) opens ON opens.subscriber_id = s.id
      SET s.engagement_score = CASE
            WHEN COALESCE(sent.sent_count, 0) < :minSentCount THEN NULL{$neverAskedCondition}
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
    if ($neverAskedCondition) {
      $parameters['unknownConsent'] = SubscriberEntity::TRACKING_CONSENT_UNKNOWN;
    }
    if ($humanOpensCondition) {
      $parameters['userAgentType'] = UserAgentEntity::USER_AGENT_TYPE_HUMAN;
    }
    $this->entityManager->getConnection()->executeStatement(
      $sql,
      $parameters,
      ['ids' => ArrayParameterType::INTEGER]
    );
  }

  private function logMissingTrackingColumn(InvalidFieldNameException $e): void {
    if ($this->missingTrackingColumnLogged) {
      return;
    }
    $this->missingTrackingColumnLogged = true;
    try {
      LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->warning(
        'Engagement scores count every send because statistics_newsletters.sent_with_tracking could not be read',
        ['error' => $e->getMessage()]
      );
    } catch (\Throwable $loggingError) {
      // Scores must still update if the log cannot be written.
    }
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
