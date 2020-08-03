<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;
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

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    parent::__construct($entityManager);
    $this->entityManager = $entityManager;
  }

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL s.{id,email,firstName,lastName,status,createdAt}");
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
    $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search); // escape for 'LIKE'
    $queryBuilder
      ->andWhere('s.subject LIKE :search')
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
    return [];// TODO
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
      ->leftJoin('s.subscriberSegments', 'ssg', Join::WITH, $queryBuilderNoSegment->expr()->eq('ssg.status', ':statusSubscribed'))
      ->leftJoin('ssg.segment', 'sg', Join::WITH, $queryBuilderNoSegment->expr()->isNull('sg.deletedAt'))
      ->where('deletedAt IS NULL')
      ->where('sg.id IS NULL')
      ->setParameter('statusSubscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()->getSingleScalarResult();

    $subscribersWithoutSegmentLabel = sprintf(
      WPFunctions::get()->__('Subscribers without a list (%s)', 'mailpoet'),
      number_format((float)$subscribersWithoutSegment)
    );

    $queryBuilder
      ->select('sg.id, sg.name, COUNT(s) AS subscribersCount')
      ->join('s.subscriberSegments', 'ssg')
      ->join('ssg.segment', 'sg')
      ->groupBy('sg.id')
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
        'label' => sprintf('%s (%d)', $item['name'], number_format((float)$item['subscribersCount'])),
        'value' => $item['id'],
      ];
    }
    return ['segment' => $segmentList];
  }

}
