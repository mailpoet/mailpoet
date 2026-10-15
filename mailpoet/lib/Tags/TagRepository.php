<?php declare(strict_types = 1);

namespace MailPoet\Tags;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\ListingDateRangeFilterTrait;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<TagEntity>
 */
class TagRepository extends Repository {
  use ListingDateRangeFilterTrait;

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

    $deleted = 0;
    $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids, &$deleted): void {
      $subscriberTagTable = $entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement(
        "DELETE FROM $subscriberTagTable WHERE tag_id IN (:ids)",
        ['ids' => $ids],
        ['ids' => ArrayParameterType::INTEGER]
      );

      $tagsTable = $entityManager->getClassMetadata(TagEntity::class)->getTableName();
      $deleted = (int)$entityManager->getConnection()->executeStatement(
        "DELETE FROM $tagsTable WHERE id IN (:ids)",
        ['ids' => $ids],
        ['ids' => ArrayParameterType::INTEGER]
      );
    });

    // Clear Doctrine's UnitOfWork so stale references don't linger.
    $this->entityManager->clear(TagEntity::class);
    return $deleted;
  }

  /**
   * Listing with subscriber counts + search + filters + sort + pagination.
   *
   * @param array{search?: string, orderby?: string, order?: string, page?: int, per_page?: int, filter?: array{from?: string, to?: string, subscriber_ranges?: array<int, array{min: int, max: ?int}>}} $args
   * @return array{items: array<int, array{id: int, name: string, description: string, subscribers_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface}>, total: int}
   */
  public function listWithCounts(array $args = []): array {
    $search = isset($args['search']) ? trim((string)$args['search']) : '';
    $orderby = isset($args['orderby']) && is_string($args['orderby']) ? $args['orderby'] : 'name';
    $order = isset($args['order']) && strtolower((string)$args['order']) === 'desc' ? 'DESC' : 'ASC';
    $page = isset($args['page']) ? max(1, (int)$args['page']) : 1;
    $perPage = isset($args['per_page']) ? max(1, min(100, (int)$args['per_page'])) : 25;
    $filter = isset($args['filter']) && is_array($args['filter']) ? $args['filter'] : [];
    $from = isset($filter['from']) && is_string($filter['from']) && $filter['from'] !== '' ? $filter['from'] : null;
    $to = isset($filter['to']) && is_string($filter['to']) && $filter['to'] !== '' ? $filter['to'] : null;
    $ranges = isset($filter['subscriber_ranges']) && is_array($filter['subscriber_ranges']) ? $filter['subscriber_ranges'] : [];

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
    $this->applyDateRangeFilter($qb, 't.createdAt', ['from' => $from, 'to' => $to]);
    $this->applySubscriberRanges($qb, $ranges);

    /** @var array<array{id: int, name: string, description: string, created_at: mixed, updated_at: mixed, subscribersCount: int|string}> $rows */
    $rows = $qb->getQuery()->getArrayResult();

    $total = $this->countWithFilters($search, $from, $to, $ranges);

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
   * @param array<int, array{min: int, max: ?int}> $ranges
   */
  private function countWithFilters(string $search, ?string $from, ?string $to, array $ranges): int {
    if ($ranges) {
      // The subscriber-count filter needs HAVING on a grouped query, so count
      // the matching tag ids. Bounded by the number of tags.
      $qb = $this->entityManager->createQueryBuilder()
        ->select('t.id')
        ->from(TagEntity::class, 't')
        ->leftJoin('t.subscriberTags', 'st')
        ->leftJoin('st.subscriber', 's', 'WITH', 's.deletedAt IS NULL')
        ->groupBy('t.id');
      if ($search !== '') {
        $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
          ->setParameter('search', '%' . $search . '%');
      }
      $this->applyDateRangeFilter($qb, 't.createdAt', ['from' => $from, 'to' => $to]);
      $this->applySubscriberRanges($qb, $ranges);
      return count($qb->getQuery()->getArrayResult());
    }

    $qb = $this->entityManager->createQueryBuilder()
      ->select('COUNT(t.id)')
      ->from(TagEntity::class, 't');
    if ($search !== '') {
      $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
        ->setParameter('search', '%' . $search . '%');
    }
    $this->applyDateRangeFilter($qb, 't.createdAt', ['from' => $from, 'to' => $to]);
    return (int)$qb->getQuery()->getSingleScalarResult();
  }

  /**
   * Apply the subscriber-count filter as an OR of HAVING conditions on the
   * grouped subscriber count. Each range is a decade bucket; `max === null`
   * means an open-ended top bucket, and `min === 0 && max === 0` is the
   * "no subscribers" bucket.
   *
   * @param array<int, array{min: int, max: ?int}> $ranges
   */
  private function applySubscriberRanges(\MailPoetVendor\Doctrine\ORM\QueryBuilder $qb, array $ranges): void {
    if (!$ranges) {
      return;
    }
    $countExpr = 'COUNT(DISTINCT s.id)';
    $conditions = [];
    foreach ($ranges as $index => $range) {
      $min = (int)$range['min'];
      $max = $range['max'] ?? null;
      if ($max === null) {
        $conditions[] = "$countExpr >= :subMin$index";
        $qb->setParameter("subMin$index", $min);
      } elseif ($min === 0 && (int)$max === 0) {
        $conditions[] = "$countExpr = 0";
      } else {
        $conditions[] = "$countExpr BETWEEN :subMin$index AND :subMax$index";
        $qb->setParameter("subMin$index", $min);
        $qb->setParameter("subMax$index", (int)$max);
      }
    }
    $qb->andHaving('(' . implode(' OR ', $conditions) . ')');
  }

  /**
   * Build decade (power-of-ten) subscriber-count buckets sized to the site's
   * data: a "none" bucket (0), closed decade buckets, and an open-ended top
   * bucket for the largest decade present. Returns an empty list when no tag
   * has any subscriber, so the filter can be hidden.
   *
   * @return array<int, array{value: string, min: int, max: ?int}>
   */
  public function getSubscriberCountBuckets(): array {
    $max = $this->getMaxSubscribersCount();
    if ($max <= 0) {
      return [];
    }

    // Largest power of ten not exceeding $max (integer-safe, avoids log10 drift).
    $topDecade = 1;
    while ($topDecade * 10 <= $max) {
      $topDecade *= 10;
    }

    $buckets = [['value' => '0', 'min' => 0, 'max' => 0]];
    for ($decade = 1; $decade < $topDecade; $decade *= 10) {
      $buckets[] = ['value' => (string)$decade, 'min' => $decade, 'max' => $decade * 10 - 1];
    }
    $buckets[] = ['value' => (string)$topDecade, 'min' => $topDecade, 'max' => null];
    return $buckets;
  }

  private function getMaxSubscribersCount(): int {
    /** @var array<array{cnt: int|string}> $rows */
    $rows = $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT s.id) AS cnt')
      ->from(TagEntity::class, 't')
      ->leftJoin('t.subscriberTags', 'st')
      ->leftJoin('st.subscriber', 's', 'WITH', 's.deletedAt IS NULL')
      ->groupBy('t.id')
      ->getQuery()->getArrayResult();

    $max = 0;
    foreach ($rows as $row) {
      $max = max($max, (int)$row['cnt']);
    }
    return $max;
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
