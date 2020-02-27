<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Listing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Listing\ListingRepository;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class NewsletterListingRepository extends ListingRepository {
  private static $supportedStatuses = [
    NewsletterEntity::STATUS_DRAFT,
    NewsletterEntity::STATUS_SCHEDULED,
    NewsletterEntity::STATUS_SENDING,
    NewsletterEntity::STATUS_SENT,
    NewsletterEntity::STATUS_ACTIVE,
  ];

  private static $supportedTypes = [
    NewsletterEntity::TYPE_STANDARD,
    NewsletterEntity::TYPE_WELCOME,
    NewsletterEntity::TYPE_AUTOMATIC,
    NewsletterEntity::TYPE_NOTIFICATION,
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
  ];

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL n.{id,subject,hash,type,status,sentAt,updatedAt,deletedAt}");
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(NewsletterEntity::class, 'n');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // include/exclude deleted
    if ($group === 'trash') {
      $queryBuilder->andWhere('n.deletedAt IS NOT NULL');
    } else {
      $queryBuilder->andWhere('n.deletedAt IS NULL');
    }

    if (!in_array($group, self::$supportedStatuses)) {
      return;
    }

    $queryBuilder
      ->andWhere('n.status = :status')
      ->setParameter('status', $group);
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search) {
    $queryBuilder
      ->andWhere('n.subject LIKE :search')
      ->setParameter('search', "%$search%"); // TODO: escape?
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    $segmentId = $filters['segment'] ?? null;
    if ($segmentId) {
      $queryBuilder
        ->join('n.newsletterSegments', 'ns')
        ->andWhere('ns.segment = :segmentId')
        ->setParameter('segmentId', $segmentId);
    }
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    $type = $parameters['type'] ?? null;
    $group = $parameters['group'] ?? null;
    $parentId = $parameters['parent_id'] ?? null;

    if ($type) {
      $this->applyType($queryBuilder, $type, $group);
    }

    if ($parentId) {
      $queryBuilder
        ->andWhere('n.parent = :parentId')
        ->setParameter('parentId', $parentId);
    }
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    if ($sortBy === 'sentAt') {
      $queryBuilder->addSelect('CASE WHEN n.sentAt IS NULL THEN 1 ELSE 0 END AS HIDDEN sentAtIsNull');
      $queryBuilder->addOrderBy('sentAtIsNull', 'DESC');
    }
    $queryBuilder->addOrderBy("n.$sortBy", $sortOrder);
  }

  private function applyType(QueryBuilder $queryBuilder, string $type, string $group = null) {
    if (!in_array($type, self::$supportedTypes)) {
      return;
    }

    if ($type === NewsletterEntity::TYPE_AUTOMATIC && $group) {
      $queryBuilder
        ->join('n.options', 'o')
        ->join('o.optionField', 'opf')
        ->andWhere('o.value = :group')
        ->setParameter('group', $group)
        ->andWhere('opf.newsletterType = n.type');
    } else {
      $queryBuilder
        ->andWhere('n.type = :type')
        ->setParameter('type', $type);
    }
  }
}
