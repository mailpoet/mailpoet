<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * Powers the "Unprocessed" tab of the Sending Status screen. Reads the lean
 * queue table (`scheduled_task_queued_subscribers`), which holds only the
 * pending recipients of in-flight sending tasks. There is no processed/failed/
 * error column here — those live in the log and are surfaced by
 * {@see ScheduledTaskSubscribersListingRepository}.
 */
class ScheduledTaskQueuedSubscribersListingRepository extends ListingRepository {
  public function getGroups(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyParameters($queryBuilder, $definition->getParameters());
    $queryBuilder->select('COUNT(stsq.subscriber) AS subscriberCount');
    $count = (int)$queryBuilder->getQuery()->getSingleScalarResult();

    return [
      [
        'name' => 'unprocessed',
        'label' => __('Unprocessed', 'mailpoet'),
        'count' => $count,
      ],
    ];
  }

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select('PARTIAL stsq.{task,subscriber}, PARTIAL s.{id, email, firstName, lastName}');
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(ScheduledTaskQueuedSubscriberEntity::class, 'stsq')
      ->leftJoin('stsq.subscriber', 's');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // The queue table is, by definition, the unprocessed set — there is no
    // sub-group to filter on.
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    // Ordering by subscriberId is mapped to email for consistency with the
    // Subscriber listing; the queue exposes no other sortable column.
    $sortBy = $sortBy === 'subscriberId' ? 's.email' : 'stsq.subscriber';
    $queryBuilder->addOrderBy($sortBy, $sortOrder);
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters = []) {
    $search = Helpers::escapeSearch($search);
    $queryBuilder
      ->andWhere('s.email LIKE :search or s.firstName LIKE :search or s.lastName LIKE :search')
      ->setParameter('search', "%$search%");
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    // the parent class requires this method, but the queue listing doesn't currently support filters.
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    if (isset($parameters['task_ids']) && !empty($parameters['task_ids'])) {
      $queryBuilder->andWhere('stsq.task IN (:taskIds)')
        ->setParameter('taskIds', $parameters['task_ids']);
    }
  }

  public function getCount(ListingDefinition $definition): int {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyConstraints($queryBuilder, $definition);
    $queryBuilder->select('COUNT(DISTINCT stsq.subscriber)');
    return intval($queryBuilder->getQuery()->getSingleScalarResult());
  }
}
