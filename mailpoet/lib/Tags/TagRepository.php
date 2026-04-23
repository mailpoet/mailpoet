<?php declare(strict_types = 1);

namespace MailPoet\Tags;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;

/**
 * @extends Repository<TagEntity>
 */
class TagRepository extends Repository {
  protected function getEntityClassName() {
    return TagEntity::class;
  }

  public function createOrUpdate(array $data = []): TagEntity {
    if (!$data['name']) {
      throw new \InvalidArgumentException('Missing name');
    }
    $tag = $this->findOneBy([
      'name' => $data['name'],
    ]);
    if (!$tag) {
      $tag = new TagEntity($data['name']);
      $this->persist($tag);
    }

    try {
      $this->flush();
    } catch (\Exception $e) {
      throw new \RuntimeException("Error when saving tag " . $data['name']);
    }
    return $tag;
  }

  /**
   * Deletes a tag along with any subscriber_tag rows referencing it.
   */
  public function deleteTag(TagEntity $tag): void {
    $this->bulkDelete([$tag->getId()]);
  }

  /**
   * Deletes multiple tags along with any subscriber_tag rows referencing them.
   *
   * @param array<int|null> $ids
   * @return int Number of tags deleted.
   */
  public function bulkDelete(array $ids): int {
    $ids = array_values(array_filter(array_map(
      static function ($id): int {
        return (int)$id;
      },
      $ids
    )));
    if (!$ids) {
      return 0;
    }
    $subscriberTagTable = $this->entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "DELETE FROM $subscriberTagTable WHERE tag_id IN (:ids)",
      ['ids' => $ids],
      ['ids' => ArrayParameterType::INTEGER]
    );

    $tagsTable = $this->entityManager->getClassMetadata(TagEntity::class)->getTableName();
    $deleted = (int)$this->entityManager->getConnection()->executeStatement(
      "DELETE FROM $tagsTable WHERE id IN (:ids)",
      ['ids' => $ids],
      ['ids' => ArrayParameterType::INTEGER]
    );

    // Clear Doctrine's UnitOfWork so stale references don't linger.
    $this->entityManager->clear(TagEntity::class);
    return $deleted;
  }

  /**
   * Listing with subscriber counts + search + sort + pagination.
   *
   * @param array{search?: string, orderby?: string, order?: string, page?: int, per_page?: int} $args
   * @return array{items: array<int, array{id: int, name: string, description: string, subscribers_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface}>, total: int}
   */
  public function listWithCounts(array $args = []): array {
    $search = isset($args['search']) ? trim((string)$args['search']) : '';
    $orderby = isset($args['orderby']) && is_string($args['orderby']) ? $args['orderby'] : 'name';
    $order = isset($args['order']) && strtolower((string)$args['order']) === 'desc' ? 'DESC' : 'ASC';
    $page = isset($args['page']) ? max(1, (int)$args['page']) : 1;
    $perPage = isset($args['per_page']) ? max(1, min(100, (int)$args['per_page'])) : 25;

    $sortable = [
      'name' => 't.name',
      'created_at' => 't.createdAt',
      'subscribers_count' => 'subscribersCount',
    ];
    $orderByExpr = $sortable[$orderby] ?? $sortable['name'];

    $qb = $this->entityManager->createQueryBuilder()
      ->select('t.id AS id, t.name AS name, t.description AS description, t.createdAt AS created_at, t.updatedAt AS updated_at, COUNT(DISTINCT s.id) AS subscribersCount')
      ->from(TagEntity::class, 't')
      ->leftJoin('t.subscriberTags', 'st')
      ->leftJoin('st.subscriber', 's', 'WITH', 's.deletedAt IS NULL')
      ->groupBy('t.id')
      ->orderBy($orderByExpr, $order);

    // Add deterministic secondary ordering so paginated results are stable when the primary sort has ties.
    if ($orderby !== 'name') {
      $qb->addOrderBy('t.name', 'ASC');
    }
    $qb->addOrderBy('t.id', 'ASC')
      ->setFirstResult(($page - 1) * $perPage)
      ->setMaxResults($perPage);

    if ($search !== '') {
      $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }

    /** @var array<array{id: int, name: string, description: string, created_at: mixed, updated_at: mixed, subscribersCount: int|string}> $rows */
    $rows = $qb->getQuery()->getArrayResult();

    $countQb = $this->entityManager->createQueryBuilder()
      ->select('COUNT(t.id)')
      ->from(TagEntity::class, 't');
    if ($search !== '') {
      $countQb->andWhere('t.name LIKE :search OR t.description LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }
    $total = (int)$countQb->getQuery()->getSingleScalarResult();

    $items = [];
    foreach ($rows as $row) {
      $createdAt = $row['created_at'] ?? null;
      $updatedAt = $row['updated_at'] ?? null;
      $items[] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'description' => (string)$row['description'],
        'subscribers_count' => (int)$row['subscribersCount'],
        'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt : null,
        'updated_at' => $updatedAt instanceof \DateTimeInterface ? $updatedAt : null,
      ];
    }

    return ['items' => $items, 'total' => $total];
  }

  /**
   * Count non-deleted subscribers attached to a tag.
   */
  public function getSubscribersCount(int $tagId): int {
    $qb = $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT s.id)')
      ->from(TagEntity::class, 't')
      ->leftJoin('t.subscriberTags', 'st')
      ->leftJoin('st.subscriber', 's', 'WITH', 's.deletedAt IS NULL')
      ->where('t.id = :id')
      ->setParameter('id', $tagId);
    return (int)$qb->getQuery()->getSingleScalarResult();
  }

  public function getSubscriberStatisticsCount(?string $status, bool $isDeleted): array {
    $qb = $this->entityManager->createQueryBuilder()
      ->select('t.id, t.name, COUNT(st) AS subscribersCount')
      ->from(TagEntity::class, 't')
      ->leftJoin('t.subscriberTags', 'st')
      ->join('st.subscriber', 's')
      ->groupBy('t.id')
      ->orderBy('t.name');

    if ($isDeleted) {
      $qb->andWhere('s.deletedAt IS NOT NULL');
    } else {
      $qb->andWhere('s.deletedAt IS NULL');
    }

    if ($status) {
      $qb->andWhere('s.status = :status')
        ->setParameter('status', $status);
    }

    return $qb->getQuery()->getArrayResult();
  }
}
