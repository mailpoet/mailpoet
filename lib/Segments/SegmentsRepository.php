<?php

namespace MailPoet\Segments;

use DateTime;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\NotFoundException;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<SegmentEntity>
 */
class SegmentsRepository extends Repository {

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(
    EntityManager $entityManager,
    NewsletterSegmentRepository $newsletterSegmentRepository,
    FormsRepository $formsRepository
  ) {
    parent::__construct($entityManager);
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
    $this->formsRepository = $formsRepository;
  }

  protected function getEntityClassName() {
    return SegmentEntity::class;
  }

  public function getWPUsersSegment() {
    return $this->findOneBy(['type' => SegmentEntity::TYPE_WP_USERS]);
  }

  public function getWooCommerceSegment(): SegmentEntity {
    $segment = $this->findOneBy(['type' => SegmentEntity::TYPE_WC_USERS]);
    if (!$segment) {
      // create the WooCommerce customers segment
      $segment = new SegmentEntity(
        WPFunctions::get()->__('WooCommerce Customers', 'mailpoet'),
        SegmentEntity::TYPE_WC_USERS,
        WPFunctions::get()->__('This list contains all of your WooCommerce customers.', 'mailpoet')
      );
      $this->entityManager->persist($segment);
      $this->entityManager->flush();
    }
    return $segment;
  }

  public function getCountsPerType(): array {
    $results = $this->doctrineRepository->createQueryBuilder('s')
      ->select('s.type, COUNT(s) as cnt')
      ->where('s.deletedAt IS NULL')
      ->groupBy('s.type')
      ->getQuery()
      ->getResult();

    $countMap = [];
    foreach ($results as $result) {
      $countMap[$result['type']] = (int)$result['cnt'];
    }
    return $countMap;
  }

  public function isNameUnique(string $name, ?int $id): bool {
    $qb = $this->doctrineRepository->createQueryBuilder('s')
      ->select('s')
      ->where('s.name = :name')
      ->setParameter('name', $name);

    if ($id !== null) {
      $qb->andWhere('s.id != :id')
        ->setParameter('id', $id);
    }

    $results = $qb->getQuery()
      ->getResult();

    return count($results) === 0;
  }

  /**
   * @param DynamicSegmentFilterData[] $filtersData
   */
  public function createOrUpdate(
    string $name,
    string $description = '',
    string $type = SegmentEntity::TYPE_DEFAULT,
    array $filtersData = [],
    ?int $id = null
  ): SegmentEntity {
    if ($id) {
      $segment = $this->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        throw new NotFoundException("Segment with ID [{$id}] was not found.");
      }
      $segment->setName($name);
      $segment->setDescription($description);
    } else {
      $segment = new SegmentEntity($name, $type, $description);
      $this->persist($segment);
    }

    // We want to remove redundant filters before update
    while ($segment->getDynamicFilters()->count() > count($filtersData)) {
      $filterEntity = $segment->getDynamicFilters()->last();
      $segment->getDynamicFilters()->removeElement($filterEntity);
      $this->entityManager->remove($filterEntity);
    }
    foreach ($filtersData as $key => $filterData) {
      if ($filterData instanceof DynamicSegmentFilterData) {
        $filterEntity = $segment->getDynamicFilters()->get($key);
        if (!$filterEntity instanceof DynamicSegmentFilterEntity) {
          $filterEntity = new DynamicSegmentFilterEntity($segment, $filterData);
          $segment->getDynamicFilters()->add($filterEntity);
          $this->entityManager->persist($filterEntity);
        } else {
          $filterEntity->setFilterData($filterData);
        }
      }
    }
    $this->flush();
    return $segment;
  }

  public function bulkDelete(array $ids, $type = SegmentEntity::TYPE_DEFAULT) {
    if (empty($ids)) {
      return 0;
    }

    return $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids, $type) {
      $subscriberSegmentTable = $entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $segmentTable = $entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
      $segmentFiltersTable = $entityManager->getClassMetadata(DynamicSegmentFilterEntity::class)->getTableName();

      $entityManager->getConnection()->executeUpdate("
         DELETE ss FROM $subscriberSegmentTable ss
         JOIN $segmentTable s ON ss.`segment_id` = s.`id`
         WHERE ss.`segment_id` IN (:ids)
         AND s.`type` = :type
      ", [
        'ids' => $ids,
        'type' => $type,
      ], ['ids' => Connection::PARAM_INT_ARRAY]);

      $entityManager->getConnection()->executeUpdate("
         DELETE df FROM $segmentFiltersTable df
         WHERE df.`segment_id` IN (:ids)
      ", [
        'ids' => $ids,
      ], ['ids' => Connection::PARAM_INT_ARRAY]);

      return $entityManager->getConnection()->executeUpdate("
         DELETE s FROM $segmentTable s
         WHERE s.`id` IN (:ids)
         AND s.`type` = :type
      ", [
        'ids' => $ids,
        'type' => $type,
      ], ['ids' => Connection::PARAM_INT_ARRAY]);
    });
  }

  public function bulkTrash(array $ids, string $type = SegmentEntity::TYPE_DEFAULT): int {
    $activelyUsedInNewsletters = $this->newsletterSegmentRepository->getSubjectsOfActivelyUsedEmailsForSegments($ids);
    $activelyUsedInForms = $this->formsRepository->getNamesOfFormsForSegments();
    $activelyUsed = array_unique(array_merge(array_keys($activelyUsedInNewsletters), array_keys($activelyUsedInForms)));
    $ids = array_diff($ids, $activelyUsed);
    return $this->updateDeletedAt($ids, new Carbon(), $type);
  }

  public function bulkRestore(array $ids, string $type = SegmentEntity::TYPE_DEFAULT): int {
    return $this->updateDeletedAt($ids, null, $type);
  }

  private function updateDeletedAt(array $ids, ?DateTime $deletedAt, string $type): int {
    if (empty($ids)) {
      return 0;
    }

    $rows = $this->entityManager->createQueryBuilder()->update(SegmentEntity::class, 's')
    ->set('s.deletedAt', ':deletedAt')
    ->where('s.id IN (:ids)')
    ->andWhere('s.type IN (:type)')
    ->setParameter('deletedAt', $deletedAt)
    ->setParameter('ids', $ids)
    ->setParameter('type', $type)
    ->getQuery()->execute();

    return $rows;
  }

  public function findByUpdatedScoreNotInLastDay(int $limit): array {
    $dateTime = (new Carbon())->subDay();
    return $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SegmentEntity::class, 's')
      ->where('s.averageEngagementScoreUpdatedAt IS NULL')
      ->orWhere('s.averageEngagementScoreUpdatedAt < :dateTime')
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->setMaxResults($limit)
      ->getResult();
  }
}
