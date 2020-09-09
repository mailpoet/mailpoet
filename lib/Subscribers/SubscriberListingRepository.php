<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class SubscriberListingRepository extends ListingRepository {
  private static $supportedStatuses = [
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
    SubscriberEntity::STATUS_BOUNCED,
    SubscriberEntity::STATUS_UNCONFIRMED,
  ];

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL s.{id,email,firstName,lastName,status,createdAt,countConfirmations,wpUserId,isWoocommerceUser}");
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(SubscriberEntity::class, 's');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // include/exclude deleted
    if ($group === 'trash') {
      $queryBuilder->andWhere('s.deletedAt IS NOT NULL');
    } else {
      $queryBuilder->andWhere('s.deletedAt IS NULL');
    }

    if (!in_array($group, self::$supportedStatuses)) {
      return;
    }

    $queryBuilder
      ->andWhere('s.status = :status')
      ->setParameter('status', $group);
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search) {
    $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($search)); // escape for 'LIKE'
    $queryBuilder
      ->andWhere('s.email LIKE :search or s.firstName LIKE :search or s.lastName LIKE :search')
      ->setParameter('search', "%$search%");
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    // this is done in a different level
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    // nothing to do here
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    $queryBuilder->addOrderBy("s.$sortBy", $sortOrder);
  }

  public function getGroups(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);

    // total count
    $countQueryBuilder = clone $queryBuilder;
    $countQueryBuilder->select('COUNT(s) AS subscribersCount');
    $countQueryBuilder->andWhere('s.deletedAt IS NULL');
    $totalCount = (int)$countQueryBuilder->getQuery()->getSingleScalarResult();

    // trashed count
    $trashedCountQueryBuilder = clone $queryBuilder;
    $trashedCountQueryBuilder->select('COUNT(s) AS subscribersCount');
    $trashedCountQueryBuilder->andWhere('s.deletedAt IS NOT NULL');
    $trashedCount = (int)$trashedCountQueryBuilder->getQuery()->getSingleScalarResult();

    // count-by-status query
    $queryBuilder->select('s.status, COUNT(s) AS subscribersCount');
    $queryBuilder->andWhere('s.deletedAt IS NULL');
    $queryBuilder->groupBy('s.status');

    $map = [];
    foreach ($queryBuilder->getQuery()->getResult() as $item) {
      $map[$item['status']] = (int)$item['subscribersCount'];
    }

    return [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => $totalCount,
      ],
      [
        'name' => SubscriberEntity::STATUS_SUBSCRIBED,
        'label' => WPFunctions::get()->__('Subscribed', 'mailpoet'),
        'count' => $map[SubscriberEntity::STATUS_SUBSCRIBED] ?? 0,
      ],
      [
        'name' => SubscriberEntity::STATUS_UNCONFIRMED,
        'label' => WPFunctions::get()->__('Unconfirmed', 'mailpoet'),
        'count' => $map[SubscriberEntity::STATUS_UNCONFIRMED] ?? 0,
      ],
      [
        'name' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'label' => WPFunctions::get()->__('Unsubscribed', 'mailpoet'),
        'count' => $map[SubscriberEntity::STATUS_UNSUBSCRIBED] ?? 0,
      ],
      [
        'name' => SubscriberEntity::STATUS_INACTIVE,
        'label' => WPFunctions::get()->__('Inactive', 'mailpoet'),
        'count' => $map[SubscriberEntity::STATUS_INACTIVE] ?? 0,
      ],
      [
        'name' => SubscriberEntity::STATUS_BOUNCED,
        'label' => WPFunctions::get()->__('Bounced', 'mailpoet'),
        'count' => $map[SubscriberEntity::STATUS_BOUNCED] ?? 0,
      ],
      [
        'name' => 'trash',
        'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
        'count' => $trashedCount,
      ],
    ];
  }

  public function getFilters(ListingDefinition $definition): array {
    $group = $definition->getGroup();

    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);

    if ($group) {
      $this->applyGroup($queryBuilder, $group);
    }

    $queryBuilderNoSegment = clone $queryBuilder;
    $subscribersWithoutSegment = $queryBuilderNoSegment
      ->select('COUNT(s) AS subscribersCount')
      ->leftJoin('s.subscriberSegments', 'ssg', Join::WITH, (string)$queryBuilderNoSegment->expr()->eq('ssg.status', ':statusSubscribed'))
      ->leftJoin('ssg.segment', 'sg', Join::WITH, (string)$queryBuilderNoSegment->expr()->isNull('sg.deletedAt'))
      ->andWhere('s.deletedAt IS NULL')
      ->andWhere('sg.id IS NULL')
      ->setParameter('statusSubscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()->getSingleScalarResult();

    $subscribersWithoutSegmentLabel = sprintf(
      WPFunctions::get()->__('Subscribers without a list (%s)', 'mailpoet'),
      number_format((float)$subscribersWithoutSegment)
    );

    $queryBuilder
      ->select('sg.id, sg.name, COUNT(s) AS subscribersCount')
      ->leftJoin('s.subscriberSegments', 'ssg')
      ->join('ssg.segment', 'sg')
      ->groupBy('sg.id')
      ->andWhere('sg.deletedAt IS NULL')
      ->andWhere('s.deletedAt IS NULL')
      ->orderBy('sg.name')
      ->having('subscribersCount > 0');

    // format segment list
    $segmentList = [
      [
        'label' => WPFunctions::get()->__('All Lists', 'mailpoet'),
        'value' => '',
      ],
    ];

    $segmentList[] = [
      'label' => $subscribersWithoutSegmentLabel,
      'value' => 'none',
    ];

    foreach ($queryBuilder->getQuery()->getResult() as $item) {
      $segmentList[] = [
        'label' => sprintf('%s (%s)', $item['name'], number_format((float)$item['subscribersCount'])),
        'value' => $item['id'],
      ];
    }
    return ['segment' => $segmentList];
  }
}
