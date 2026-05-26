<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscriberScore implements Filter {
  const TYPE = 'subscriberScore';

  const HIGHER_THAN = 'higherThan';
  const LOWER_THAN = 'lowerThan';
  const EQUALS = 'equals';
  const NOT_EQUALS = 'not_equals';
  const UNKNOWN = 'unknown';
  const NOT_UNKNOWN = 'not_unknown';
  const DORMANT = 'dormant';
  const NOT_DORMANT = 'not_dormant';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');
    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameter = 'score' . $parameterSuffix;

    if ($operator === self::HIGHER_THAN) {
      $queryBuilder->andWhere("engagement_score > :$parameter");
    } elseif ($operator === self::LOWER_THAN) {
      $queryBuilder->andWhere("engagement_score < :$parameter");
    } elseif ($operator === self::EQUALS) {
      $queryBuilder->andWhere("engagement_score = :$parameter");
    } elseif ($operator === self::NOT_EQUALS) {
      $queryBuilder->andWhere("engagement_score != :$parameter");
    } elseif ($operator === self::UNKNOWN) {
      $queryBuilder->andWhere($this->getUnknownEngagementScoreCondition());
    } elseif ($operator === self::NOT_UNKNOWN) {
      $queryBuilder->andWhere('NOT ' . $this->getUnknownEngagementScoreCondition());
    } elseif ($operator === self::DORMANT) {
      $queryBuilder->andWhere($this->getDormantEngagementScoreCondition());
      $queryBuilder->setParameter('engagement_score_recent_cutoff', (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'));
    } elseif ($operator === self::NOT_DORMANT) {
      $queryBuilder->andWhere('NOT ' . $this->getDormantEngagementScoreCondition());
      $queryBuilder->setParameter('engagement_score_recent_cutoff', (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'));
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, is_numeric($value) ? (int)$value : 0);

    return $queryBuilder;
  }

  private function getUnknownEngagementScoreCondition(): string {
    $lifetimeSentCount = $this->getSentCountSubquery();
    return sprintf(
      '(engagement_score IS NULL AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getDormantEngagementScoreCondition(): string {
    $lifetimeSentCount = $this->getSentCountSubquery();
    $recentSentCount = $this->getSentCountSubquery('engagement_score_recent_cutoff');
    return sprintf(
      '(engagement_score IS NULL AND %s >= %d AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE,
      $recentSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getSentCountSubquery(?string $sentAtParameter = null): string {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $statisticsNewslettersTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $sentAtCondition = $sentAtParameter ? " AND engagement_score_stats.sent_at >= :$sentAtParameter" : '';
    return sprintf(
      '(SELECT COUNT(DISTINCT engagement_score_stats.newsletter_id) FROM %s engagement_score_stats WHERE engagement_score_stats.subscriber_id = %s.id%s)',
      $statisticsNewslettersTable,
      $subscribersTable,
      $sentAtCondition
    );
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
