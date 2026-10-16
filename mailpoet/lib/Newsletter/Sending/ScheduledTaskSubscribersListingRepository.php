<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Andx;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class ScheduledTaskSubscribersListingRepository extends ListingRepository {
  public const ALLOWED_RESULTS = [
    ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT,
    ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED,
    ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED,
  ];

  public function getGroups(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyParameters($queryBuilder, $definition->getParameters());

    // total count
    $countQueryBuilder = clone $queryBuilder;
    $countQueryBuilder->select('COUNT(sts.subscriber) AS subscriberCount');
    $totalCount = intval($countQueryBuilder->getQuery()->getSingleScalarResult());

    $countResult = function (string $result) use ($queryBuilder): int {
      $resultQueryBuilder = clone $queryBuilder;
      $resultQueryBuilder->select('COUNT(sts.subscriber) AS subscriberCount');
      $predicate = $this->getResultPredicate($resultQueryBuilder, $result, 'group');
      if ($predicate !== null) {
        $resultQueryBuilder->andWhere($predicate);
      }
      return intval($resultQueryBuilder->getQuery()->getSingleScalarResult());
    };
    $sentCount = $countResult(ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT);
    $failedCount = $countResult(ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED);
    $unprocessedCount = $countResult(ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED);

    return [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => $totalCount,
      ],
      [
        'name' => ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT,
        'label' => __('Sent', 'mailpoet'),
        'count' => $sentCount,
      ],
      [
        'name' => ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED,
        'label' => __('Failed', 'mailpoet'),
        'count' => $failedCount,
      ],
      [
        'name' => ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED,
        'label' => __('Unprocessed', 'mailpoet'),
        'count' => $unprocessedCount,
      ],
    ];
  }

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL sts.{task,subscriber,processed,failed,error,createdAt,updatedAt}, PARTIAL s.{id, email, firstName, lastName}");
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->leftJoin('sts.subscriber', 's');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    $predicate = $this->getResultPredicate($queryBuilder, $group, 'group');
    if ($predicate !== null) {
      $queryBuilder->andWhere($predicate);
    }
  }

  /**
   * Builds the condition for one sending result. Parameter names are prefixed so that
   * the group and the result filter can be applied to the same query.
   * @return Andx|string|null
   */
  private function getResultPredicate(QueryBuilder $queryBuilder, string $result, string $prefix) {
    if ($result === ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT) {
      $queryBuilder->setParameter("{$prefix}ProcessedStatus", ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
      $queryBuilder->setParameter("{$prefix}FailedStatus", ScheduledTaskSubscriberEntity::FAIL_STATUS_OK);
      return $queryBuilder->expr()->andX(
        "sts.processed = :{$prefix}ProcessedStatus",
        "sts.failed = :{$prefix}FailedStatus"
      );
    }
    if ($result === ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED) {
      $queryBuilder->setParameter("{$prefix}FailedStatus", ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED);
      return "sts.failed = :{$prefix}FailedStatus";
    }
    if ($result === ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED) {
      $queryBuilder->setParameter("{$prefix}ProcessedStatus", ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
      return "sts.processed = :{$prefix}ProcessedStatus";
    }
    return null;
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    // ScheduledTaskSubscriber doesn't have id column so the default fallback value 'id'
    // generated in MailPoet\Listing\Handler needs to be changed to something else
    if ($sortBy === 'id') {
      $sortBy = 'sts.subscriber';
    } elseif ($sortBy === 'subscriberId') { // Ordering by subscriberId is mapped to email for consistency with Subscriber listing
      $sortBy = 's.email';
    } else {
      $sortBy = "sts.{$sortBy}";
    }
    $queryBuilder->addOrderBy($sortBy, $sortOrder);
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters = []) {
    $search = Helpers::escapeSearch($search);
    $queryBuilder
      ->andWhere('s.email LIKE :search or s.firstName LIKE :search or s.lastName LIKE :search')
      ->setParameter('search', "%$search%");
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    $result = $filters['result'] ?? null;
    if ($result === null || $result === '' || $result === []) {
      return;
    }
    $results = is_array($result) ? $result : [$result];
    $results = array_values(array_intersect(self::ALLOWED_RESULTS, array_filter($results, 'is_string')));
    if (!$results) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }
    $predicates = [];
    foreach ($results as $value) {
      $predicates[] = $this->getResultPredicate($queryBuilder, $value, 'result' . ucfirst($value));
    }
    $queryBuilder->andWhere($queryBuilder->expr()->orX(...$predicates));
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    if (isset($parameters['task_ids']) && !empty($parameters['task_ids'])) {
      $queryBuilder->andWhere('sts.task IN (:taskIds)')
        ->setParameter('taskIds', $parameters['task_ids']);
    }
  }

  public function getCount(ListingDefinition $definition): int {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyConstraints($queryBuilder, $definition);
    $queryBuilder->select("COUNT(DISTINCT sts.subscriber)");
    return intval($queryBuilder->getQuery()->getSingleScalarResult());
  }
}
