<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\CustomFields;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<CustomFieldEntity>
 */
class CustomFieldsRepository extends Repository {
  public function __construct(
    EntityManager $entityManager
  ) {
    parent::__construct($entityManager);
  }

  protected function getEntityClassName() {
    return CustomFieldEntity::class;
  }

  /**
   * @param array $data
   * @return CustomFieldEntity
   */
  public function createOrUpdate($data) {
    // set name as label by default
    if (empty($data['params']['label']) && isset($data['name'])) {
      $data['params']['label'] = $data['name'];
    }

    if (isset($data['id'])) {
      $field = $this->findOneById((int)$data['id']);
    } elseif (isset($data['name'])) {
      $field = $this->findOneBy(['name' => $data['name']]);
    }
    if (!isset($field)) {
      $field = new CustomFieldEntity();
      $this->entityManager->persist($field);
    }
    if (isset($data['name'])) $field->setName($data['name']);
    if (isset($data['type'])) $field->setType($data['type']);
    if (isset($data['params'])) $field->setParams($data['params']);
    $this->entityManager->flush();
    return $field;
  }

  public function findAllAsArray() {
    $customFieldsTable = $this->entityManager->getClassMetadata(CustomFieldEntity::class)->getTableName();

    $query = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select('*')
      ->from($customFieldsTable)
      ->where('deleted_at IS NULL')
      ->execute();

    return $query->fetchAllAssociative();
  }

  /**
   * @return CustomFieldEntity[]
   */
  public function findAllActive(): array {
    return $this->findBy(['deletedAt' => null], ['createdAt' => 'asc']);
  }

  public function deleteCustomField(CustomFieldEntity $customField): void {
    $this->bulkTrash([(int)$customField->getId()]);
  }

  public function hasSubscriberValues(int $customFieldId): bool {
    $count = (int)$this->entityManager->createQueryBuilder()
      ->select('COUNT(scf.id)')
      ->from(SubscriberCustomFieldEntity::class, 'scf')
      ->where('scf.customField = :id')
      ->setParameter('id', $customFieldId)
      ->setMaxResults(1)
      ->getQuery()->getSingleScalarResult();
    return $count > 0;
  }

  /**
   * @param array<int|null> $ids
   */
  public function bulkTrash(array $ids): int {
    $ids = $this->normalizeIds($ids);
    if (!$ids) {
      return 0;
    }

    $result = $this->entityManager->createQueryBuilder()
      ->update(CustomFieldEntity::class, 'cf')
      ->set('cf.deletedAt', 'CURRENT_TIMESTAMP()')
      ->where('cf.id IN (:ids)')
      ->andWhere('cf.deletedAt IS NULL')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    $this->refreshAll(function (CustomFieldEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });
    return $result;
  }

  /**
   * @param array<int|null> $ids
   */
  public function bulkRestore(array $ids): int {
    $ids = $this->normalizeIds($ids);
    if (!$ids) {
      return 0;
    }

    $result = $this->entityManager->createQueryBuilder()
      ->update(CustomFieldEntity::class, 'cf')
      ->set('cf.deletedAt', ':deletedAt')
      ->where('cf.id IN (:ids)')
      ->andWhere('cf.deletedAt IS NOT NULL')
      ->setParameter('deletedAt', null)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    $this->refreshAll(function (CustomFieldEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });
    return $result;
  }

  /**
   * Permanently deletes trashed custom fields and removes references from
   * subscriber values, forms, and dynamic segments.
   *
   * @param array<int|null> $ids
   * @return int Number of custom fields deleted.
   */
  public function bulkDelete(array $ids): int {
    $ids = $this->normalizeIds($ids);
    if (!$ids) {
      return 0;
    }
    $ids = $this->findTrashedIds($ids);
    return $this->deleteTrashedByIds($ids);
  }

  public function emptyTrash(): int {
    return $this->deleteTrashedByIds($this->findTrashedIds());
  }

  /**
   * @param int[] $ids
   */
  private function deleteTrashedByIds(array $ids): int {
    if (!$ids) {
      return 0;
    }
    $deleted = 0;
    $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids, &$deleted): void {
      $this->removeCustomFieldsFromForms($ids);
      $this->removeCustomFieldsFromDynamicSegments($ids);

      $subscriberCustomFieldTable = $entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement(
        "DELETE FROM $subscriberCustomFieldTable WHERE custom_field_id IN (:ids)",
        ['ids' => $ids],
        ['ids' => ArrayParameterType::INTEGER]
      );

      $customFieldsTable = $entityManager->getClassMetadata(CustomFieldEntity::class)->getTableName();
      $deleted = (int)$entityManager->getConnection()->executeStatement(
        "DELETE FROM $customFieldsTable WHERE id IN (:ids)",
        ['ids' => $ids],
        ['ids' => ArrayParameterType::INTEGER]
      );
    });

    $this->entityManager->clear(CustomFieldEntity::class);
    $this->entityManager->clear(SubscriberCustomFieldEntity::class);
    $this->entityManager->clear(FormEntity::class);
    $this->entityManager->clear(DynamicSegmentFilterEntity::class);
    return $deleted;
  }

  /**
   * @param array{search?: string, orderby?: string, order?: string, page?: int, per_page?: int, group?: string} $args
   * @return array{items: array<int, array{id: int, name: string, label: string, type: string, params: array, subscribers_count: int, forms_count: int, dynamic_segments_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface, deleted_at: ?\DateTimeInterface}>, total: int, groups: array<int, array{name: string, label: string, count: int}>}
   */
  public function listWithCounts(array $args = []): array {
    $search = isset($args['search']) ? trim((string)$args['search']) : '';
    $orderby = isset($args['orderby']) && is_string($args['orderby']) ? $args['orderby'] : 'name';
    $order = isset($args['order']) && strtolower((string)$args['order']) === 'desc' ? 'DESC' : 'ASC';
    $page = isset($args['page']) ? max(1, (int)$args['page']) : 1;
    $perPage = isset($args['per_page']) ? max(1, min(100, (int)$args['per_page'])) : 25;
    $group = isset($args['group']) && $args['group'] === 'trash' ? 'trash' : 'all';

    $sortable = [
      'name' => 'cf.name',
      'type' => 'cf.type',
      'created_at' => 'cf.createdAt',
      'subscribers_count' => 'subscribersCount',
    ];
    $orderByExpr = $sortable[$orderby] ?? $sortable['name'];

    $qb = $this->entityManager->createQueryBuilder()
      ->select('cf.id AS id, cf.name AS name, cf.type AS type, cf.params AS params, cf.createdAt AS created_at, cf.updatedAt AS updated_at, cf.deletedAt AS deleted_at, COUNT(DISTINCT s.id) AS subscribersCount')
      ->from(CustomFieldEntity::class, 'cf')
      ->leftJoin(SubscriberCustomFieldEntity::class, 'scf', 'WITH', 'scf.customField = cf')
      ->leftJoin('scf.subscriber', 's', 'WITH', 's.deletedAt IS NULL')
      ->groupBy('cf.id')
      ->orderBy($orderByExpr, $order);

    if ($orderby !== 'name') {
      $qb->addOrderBy('cf.name', 'ASC');
    }
    $qb->addOrderBy('cf.id', 'ASC')
      ->setFirstResult(($page - 1) * $perPage)
      ->setMaxResults($perPage);

    if ($search !== '') {
      $qb->andWhere('cf.name LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }
    $this->applyGroup($qb, $group);

    /** @var array<array{id: int, name: string, type: string, params: mixed, created_at: mixed, updated_at: mixed, deleted_at: mixed, subscribersCount: int|string}> $rows */
    $rows = $qb->getQuery()->getArrayResult();

    $countQb = $this->entityManager->createQueryBuilder()
      ->select('COUNT(cf.id)')
      ->from(CustomFieldEntity::class, 'cf');
    if ($search !== '') {
      $countQb->andWhere('cf.name LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }
    $this->applyGroup($countQb, $group);
    $total = (int)$countQb->getQuery()->getSingleScalarResult();
    $groups = $this->getGroups();

    $customFieldIds = array_map('intval', array_column($rows, 'id'));
    $formsCounts = $this->getFormCountsByCustomFieldIds($customFieldIds);
    $dynamicSegmentsCounts = $this->getDynamicSegmentCountsByCustomFieldIds($customFieldIds);

    $items = [];
    foreach ($rows as $row) {
      $id = (int)$row['id'];
      $params = is_array($row['params']) ? $row['params'] : [];
      $label = isset($params['label']) && is_scalar($params['label']) ? (string)$params['label'] : (string)$row['name'];
      $createdAt = $row['created_at'] ?? null;
      $updatedAt = $row['updated_at'] ?? null;
      $deletedAt = $row['deleted_at'] ?? null;
      $items[] = [
        'id' => $id,
        'name' => (string)$row['name'],
        'label' => $label,
        'type' => (string)$row['type'],
        'params' => $params,
        'subscribers_count' => (int)$row['subscribersCount'],
        'forms_count' => $formsCounts[$id] ?? 0,
        'dynamic_segments_count' => $dynamicSegmentsCounts[$id] ?? 0,
        'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt : null,
        'updated_at' => $updatedAt instanceof \DateTimeInterface ? $updatedAt : null,
        'deleted_at' => $deletedAt instanceof \DateTimeInterface ? $deletedAt : null,
      ];
    }

    return ['items' => $items, 'total' => $total, 'groups' => $groups];
  }

  /**
   * @return array<int, array{name: string, label: string, count: int}>
   */
  private function getGroups(): array {
    $activeCount = $this->countByDeletedAt(false);
    $trashedCount = $this->countByDeletedAt(true);
    return [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => $activeCount,
      ],
      [
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => $trashedCount,
      ],
    ];
  }

  private function countByDeletedAt(bool $trashed): int {
    $queryBuilder = $this->entityManager->createQueryBuilder()
      ->select('COUNT(cf.id)')
      ->from(CustomFieldEntity::class, 'cf');
    $this->applyGroup($queryBuilder, $trashed ? 'trash' : 'all');
    return (int)$queryBuilder->getQuery()->getSingleScalarResult();
  }

  private function applyGroup(\MailPoetVendor\Doctrine\ORM\QueryBuilder $queryBuilder, string $group): void {
    if ($group === 'trash') {
      $queryBuilder->andWhere('cf.deletedAt IS NOT NULL');
    } else {
      $queryBuilder->andWhere('cf.deletedAt IS NULL');
    }
  }

  /**
   * @param array<int|null> $ids
   * @return int[]
   */
  private function normalizeIds(array $ids): array {
    return array_values(array_filter(array_map(
      static function ($id): int {
        return (int)$id;
      },
      $ids
    )));
  }

  /**
   * @param int[]|null $ids
   * @return int[]
   */
  private function findTrashedIds(?array $ids = null): array {
    $customFieldsTable = $this->entityManager->getClassMetadata(CustomFieldEntity::class)->getTableName();
    $queryBuilder = $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->select('id')
      ->from($customFieldsTable)
      ->where('deleted_at IS NOT NULL');
    if ($ids !== null) {
      $queryBuilder
        ->andWhere('id IN (:ids)')
        ->setParameter('ids', $ids, ArrayParameterType::INTEGER);
    }
    $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
    $trashedIds = [];
    foreach ($rows as $row) {
      $id = $row['id'] ?? null;
      if (is_int($id) || is_string($id)) {
        $trashedIds[] = (int)$id;
      }
    }
    return $trashedIds;
  }

  /**
   * @param int[] $customFieldIds
   * @return array<int, int>
   */
  private function getFormCountsByCustomFieldIds(array $customFieldIds): array {
    if (!$customFieldIds) {
      return [];
    }

    $customFieldIdsLookup = array_flip($customFieldIds);
    $counts = array_fill_keys($customFieldIds, 0);
    /** @var FormEntity[] $forms */
    $forms = $this->entityManager->createQueryBuilder()
      ->select('f')
      ->from(FormEntity::class, 'f')
      ->where('f.deletedAt IS NULL')
      ->getQuery()
      ->getResult();

    foreach ($forms as $form) {
      $formCustomFieldIds = [];
      foreach ($form->getBlocksByTypes(FormEntity::FORM_FIELD_TYPES) as $block) {
        $customFieldId = isset($block['id']) ? (int)$block['id'] : 0;
        if (isset($customFieldIdsLookup[$customFieldId])) {
          $formCustomFieldIds[$customFieldId] = true;
        }
      }
      foreach (array_keys($formCustomFieldIds) as $customFieldId) {
        $counts[$customFieldId]++;
      }
    }

    return $counts;
  }

  /**
   * @param int[] $customFieldIds
   */
  private function removeCustomFieldsFromForms(array $customFieldIds): void {
    $customFieldIdsLookup = array_flip($customFieldIds);
    /** @var FormEntity[] $forms */
    $forms = $this->entityManager->createQueryBuilder()
      ->select('f')
      ->from(FormEntity::class, 'f')
      ->getQuery()
      ->getResult();

    foreach ($forms as $form) {
      $body = $form->getBody();
      if (!is_array($body)) {
        continue;
      }
      $updatedBody = $this->removeCustomFieldBlocks($body, $customFieldIdsLookup);
      if ($updatedBody !== $body) {
        $form->setBody($updatedBody);
      }
    }
  }

  /**
   * @param array<mixed, mixed> $blocks
   * @param array<int, int> $customFieldIdsLookup
   * @return array<int, mixed>
   */
  private function removeCustomFieldBlocks(array $blocks, array $customFieldIdsLookup): array {
    $updated = [];
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        $updated[] = $block;
        continue;
      }

      $rawCustomFieldId = $block['id'] ?? null;
      $customFieldId = is_int($rawCustomFieldId) || is_string($rawCustomFieldId) ? (int)$rawCustomFieldId : 0;
      $type = isset($block['type']) && is_string($block['type']) ? $block['type'] : null;
      if ($type !== null && in_array($type, FormEntity::FORM_FIELD_TYPES, true) && isset($customFieldIdsLookup[$customFieldId])) {
        continue;
      }

      if (isset($block['body']) && is_array($block['body'])) {
        $block['body'] = $this->removeCustomFieldBlocks($block['body'], $customFieldIdsLookup);
      }
      $updated[] = $block;
    }
    return $updated;
  }

  /**
   * @param int[] $customFieldIds
   */
  private function removeCustomFieldsFromDynamicSegments(array $customFieldIds): void {
    $customFieldIdsLookup = array_flip($customFieldIds);
    /** @var DynamicSegmentFilterEntity[] $filters */
    $filters = $this->entityManager->createQueryBuilder()
      ->select('dsf')
      ->from(DynamicSegmentFilterEntity::class, 'dsf')
      ->where('dsf.filterData.action = :action')
      ->setParameter('action', MailPoetCustomFields::TYPE)
      ->getQuery()
      ->getResult();

    foreach ($filters as $filter) {
      $customFieldIdParam = $filter->getFilterData()->getParam('custom_field_id');
      if (!is_int($customFieldIdParam) && !is_string($customFieldIdParam)) {
        continue;
      }
      if (isset($customFieldIdsLookup[(int)$customFieldIdParam])) {
        $this->entityManager->remove($filter);
      }
    }
  }

  /**
   * @param int[] $customFieldIds
   * @return array<int, int>
   */
  private function getDynamicSegmentCountsByCustomFieldIds(array $customFieldIds): array {
    if (!$customFieldIds) {
      return [];
    }

    $customFieldIdsLookup = array_flip($customFieldIds);
    $segmentIdsByCustomFieldId = array_fill_keys($customFieldIds, []);
    /** @var DynamicSegmentFilterEntity[] $filters */
    $filters = $this->entityManager->createQueryBuilder()
      ->select('dsf')
      ->from(DynamicSegmentFilterEntity::class, 'dsf')
      ->join('dsf.segment', 's')
      ->where('s.deletedAt IS NULL')
      ->andWhere('dsf.filterData.action = :action')
      ->setParameter('action', MailPoetCustomFields::TYPE)
      ->getQuery()
      ->getResult();

    foreach ($filters as $filter) {
      $customFieldIdParam = $filter->getFilterData()->getParam('custom_field_id');
      if (!is_int($customFieldIdParam) && !is_string($customFieldIdParam)) {
        continue;
      }
      $customFieldId = (int)$customFieldIdParam;
      if (!isset($customFieldIdsLookup[$customFieldId])) {
        continue;
      }
      $segment = $filter->getSegment();
      if (!$segment instanceof SegmentEntity) {
        continue;
      }
      $segmentIdsByCustomFieldId[$customFieldId][(int)$segment->getId()] = true;
    }

    $counts = [];
    foreach ($segmentIdsByCustomFieldId as $customFieldId => $segmentIds) {
      $counts[$customFieldId] = count($segmentIds);
    }
    return $counts;
  }
}
