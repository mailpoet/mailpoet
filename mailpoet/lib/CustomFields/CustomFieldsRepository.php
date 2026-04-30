<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\CustomFields;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
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
      ->execute();

    return $query->fetchAllAssociative();
  }

  /**
   * @param array{search?: string, orderby?: string, order?: string, page?: int, per_page?: int} $args
   * @return array{items: array<int, array{id: int, name: string, label: string, type: string, params: array, subscribers_count: int, forms_count: int, dynamic_segments_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface}>, total: int}
   */
  public function listWithCounts(array $args = []): array {
    $search = isset($args['search']) ? trim((string)$args['search']) : '';
    $orderby = isset($args['orderby']) && is_string($args['orderby']) ? $args['orderby'] : 'name';
    $order = isset($args['order']) && strtolower((string)$args['order']) === 'desc' ? 'DESC' : 'ASC';
    $page = isset($args['page']) ? max(1, (int)$args['page']) : 1;
    $perPage = isset($args['per_page']) ? max(1, min(100, (int)$args['per_page'])) : 25;

    $sortable = [
      'name' => 'cf.name',
      'type' => 'cf.type',
      'created_at' => 'cf.createdAt',
      'subscribers_count' => 'subscribersCount',
    ];
    $orderByExpr = $sortable[$orderby] ?? $sortable['name'];

    $qb = $this->entityManager->createQueryBuilder()
      ->select('cf.id AS id, cf.name AS name, cf.type AS type, cf.params AS params, cf.createdAt AS created_at, cf.updatedAt AS updated_at, COUNT(DISTINCT s.id) AS subscribersCount')
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

    /** @var array<array{id: int, name: string, type: string, params: mixed, created_at: mixed, updated_at: mixed, subscribersCount: int|string}> $rows */
    $rows = $qb->getQuery()->getArrayResult();

    $countQb = $this->entityManager->createQueryBuilder()
      ->select('COUNT(cf.id)')
      ->from(CustomFieldEntity::class, 'cf');
    if ($search !== '') {
      $countQb->andWhere('cf.name LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }
    $total = (int)$countQb->getQuery()->getSingleScalarResult();

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
      ];
    }

    return ['items' => $items, 'total' => $total];
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
