<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Logging;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\InvalidStateException;
use MailPoet\Util\Helpers;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;

/**
 * @extends Repository<LogEntity>
 */
class LogRepository extends Repository {
  public function saveLog(LogEntity $log): void {
    // Save log entity using DBAL to avoid calling "flush()" on the entity manager.
    // Calling "flush()" can have unintended side effects, such as saving unwanted
    // changes or trying to save entities that were detached from the entity manager.
    $this->entityManager->getConnection()->insert(
      $this->entityManager->getClassMetadata(LogEntity::class)->getTableName(),
      [
        'name' => $log->getName(),
        'level' => $log->getLevel(),
        'message' => $log->getMessage(),
        'raw_message' => $log->getRawMessage(),
        'context' => json_encode($log->getContext()),
        'created_at' => (
          $log->getCreatedAt() ?? Carbon::now()->millisecond(0)
        )->format('Y-m-d H:i:s'),
      ],
    );

    // sync the changes with the entity manager
    if ($this->entityManager->isOpen()) {
      $lastInsertId = (int)$this->entityManager->getConnection()->lastInsertId();
      $log->setId($lastInsertId);
      $this->entityManager->getUnitOfWork()->registerManaged($log, ['id' => $log->getId()], []);
      $this->entityManager->refresh($log);
    }
  }

  /**
   * @param \DateTimeInterface|null $dateFrom
   * @param \DateTimeInterface|null $dateTo
   * @param string|null $search
   * @param string $offset
   * @param string $limit
   * @return LogEntity[]
   */
  public function getLogs(
    ?\DateTimeInterface $dateFrom = null,
    ?\DateTimeInterface $dateTo = null,
    ?string $search = null,
    ?string $offset = null,
    ?string $limit = null
  ): array {
    $query = $this->doctrineRepository->createQueryBuilder('l')
      ->select('l');

    if ($dateFrom instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt >= :dateFrom')
        ->setParameter('dateFrom', $dateFrom->format('Y-m-d 00:00:00'));
    }
    if ($dateTo instanceof \DateTimeInterface) {
      $query
        ->andWhere('l.createdAt <= :dateTo')
        ->setParameter('dateTo', $dateTo->format('Y-m-d 23:59:59'));
    }
    if ($search) {
      $search = Helpers::escapeSearch($search);
      $query
        ->andWhere('l.name LIKE :search or l.message LIKE :search')
        ->setParameter('search', "%$search%");
    }

    $query->orderBy('l.createdAt', 'desc');
    if ($offset !== null) {
      $query->setFirstResult((int)$offset);
    }
    if ($limit === null) {
      $query->setMaxResults(500);
    } else {
      $query->setMaxResults((int)$limit);
    }


    return $query->getQuery()->getResult();
  }

  /**
   * Distinct log names (sources), used to populate the listing's name filter.
   *
   * @return string[]
   */
  public function getDistinctNames(): array {
    $rows = $this->entityManager->createQueryBuilder()
      ->select('DISTINCT l.name')
      ->from(LogEntity::class, 'l')
      ->where('l.name IS NOT NULL')
      ->andWhere("l.name != ''")
      ->orderBy('l.name', 'asc')
      ->getQuery()
      ->getSingleColumnResult();

    $names = [];
    foreach ($rows as $row) {
      if (is_string($row)) {
        $names[] = $row;
      }
    }
    return $names;
  }

  public function purgeOldLogs(int $daysToKeepLogs, int $limit = 1000): int {
    $logsTable = $this->entityManager->getClassMetadata(LogEntity::class)->getTableName();
    $result = $this->entityManager->getConnection()->executeStatement(
      "
      DELETE FROM `{$logsTable}`
      WHERE `created_at` < :date
      ORDER BY `created_at` ASC, `id` ASC
      LIMIT :limit
    ",
      [
      'date' => Carbon::now()->subDays($daysToKeepLogs)->toDateTimeString(),
      'limit' => $limit,
      ],
      [
      'date' => ParameterType::STRING,
      'limit' => ParameterType::INTEGER,
      ]
    );

    return (int)$result;
  }

  /**
   * Delete logs matching the listing's filter shape (`from`/`to`/`name`/`level`)
   * and free-text search, so a deletion removes exactly what the filtered
   * listing shows. Deletes in batches to keep each statement bounded on large
   * log tables, mirroring purgeOldLogs().
   *
   * @param array{from?: string, to?: string, name?: string[], level?: int[]} $filter
   */
  public function deleteLogs(array $filter, ?string $search = null, int $batchSize = 1000): int {
    $logsTable = $this->entityManager->getClassMetadata(LogEntity::class)->getTableName();
    [$where, $parameters, $types] = $this->buildFilterSql($filter, $search);
    $parameters['batch_limit'] = $batchSize;
    $types['batch_limit'] = ParameterType::INTEGER;

    $sql = "DELETE FROM `{$logsTable}`{$where} ORDER BY `created_at` ASC, `id` ASC LIMIT :batch_limit";
    $connection = $this->entityManager->getConnection();

    $deleted = 0;
    do {
      $affected = (int)$connection->executeStatement($sql, $parameters, $types);
      $deleted += $affected;
    } while ($affected === $batchSize);

    return $deleted;
  }

  /**
   * Fetch logs matching the listing's filter shape (`from`/`to`/`name`/`level`)
   * and free-text search for export, so a download contains exactly the rows the
   * filtered listing shows. Mirrors deleteLogs()'s WHERE clause and is capped by
   * $limit.
   *
   * Yields rows in batches via keyset pagination on (`created_at`, `id`) rather
   * than one big result set. MailPoet's WPDB-backed Doctrine driver buffers an
   * entire result in memory (see Doctrine\WPDB\Result), so a single
   * `LIMIT 50000` query would load every row at once and can exhaust PHP's
   * memory on large installs. Paging keeps peak memory at one batch.
   *
   * @param array{from?: string, to?: string, name?: string[], level?: int[]} $filter
   * @return iterable<int, array{created_at: string, name: string|null, message: string|null}>
   */
  public function getLogsForExport(array $filter, ?string $search = null, int $limit = 50000, int $batchSize = 1000): iterable {
    $logsTable = $this->entityManager->getClassMetadata(LogEntity::class)->getTableName();
    [$where, $baseParameters, $baseTypes] = $this->buildFilterSql($filter, $search);

    $batchSize = $batchSize > 0 ? $batchSize : 1000;
    $remaining = $limit;
    $lastCreatedAt = null;
    $lastId = null;

    while ($remaining > 0) {
      $batch = (int)min($batchSize, $remaining);
      $parameters = $baseParameters;
      $types = $baseTypes;
      $conditions = $where;

      if ($lastCreatedAt !== null) {
        // Keyset cursor: continue strictly after the last row in the same
        // (`created_at` DESC, `id` DESC) order. Stable even while new rows are
        // inserted at the top of the table during the export.
        $keyset = '(`created_at` < :ks_created_at OR (`created_at` = :ks_created_at AND `id` < :ks_id))';
        $conditions = $conditions === '' ? ' WHERE ' . $keyset : $conditions . ' AND ' . $keyset;
        $parameters['ks_created_at'] = $lastCreatedAt;
        $parameters['ks_id'] = $lastId;
        $types['ks_created_at'] = ParameterType::STRING;
        $types['ks_id'] = ParameterType::INTEGER;
      }

      $parameters['batch_limit'] = $batch;
      $types['batch_limit'] = ParameterType::INTEGER;

      $sql = "SELECT `id`, `created_at`, `name`, `message` FROM `{$logsTable}`{$conditions} ORDER BY `created_at` DESC, `id` DESC LIMIT :batch_limit";

      $rows = $this->entityManager->getConnection()
        ->executeQuery($sql, $parameters, $types)
        ->fetchAllAssociative();

      if ($rows === []) {
        break;
      }

      foreach ($rows as $row) {
        $lastCreatedAt = $this->castToNullableString($row['created_at']);
        $lastId = (int)$row['id'];
        yield [
          'created_at' => $this->castToNullableString($row['created_at']) ?? '',
          'name' => $this->castToNullableString($row['name']),
          'message' => $this->castToNullableString($row['message']),
        ];
        $remaining--;
      }

      // A short page means the table is exhausted; stop before an empty query.
      if (count($rows) < $batch) {
        break;
      }
    }
  }

  /**
   * @param mixed $value
   */
  private function castToNullableString($value): ?string {
    return is_scalar($value) ? (string)$value : null;
  }

  public function getRawMessagesForNewsletter(NewsletterEntity $newsletter, string $topic): array {
    return $this->entityManager->createQueryBuilder()
      ->select('DISTINCT logs.rawMessage message')
      ->from(LogEntity::class, 'logs')
      ->where('logs.name = :topic')
      ->andWhere('logs.context LIKE :context')
      ->orderBy('logs.createdAt')
      ->setParameter('context', json_encode(['newsletter_id' => $newsletter->getId()]))
      ->setParameter('topic', $topic)
      ->getQuery()
      ->getSingleColumnResult();
  }

  public function persist($entity): void {
    throw new InvalidStateException('Use saveLog() instead to avoid unintended side effects');
  }

  public function flush(): void {
    throw new InvalidStateException('Use saveLog() instead to avoid unintended side effects');
  }

  protected function getEntityClassName() {
    return LogEntity::class;
  }

  /**
   * Build the WHERE clause shared by log deletion. Day boundaries
   * (`00:00:00`–`23:59:59`) and the literal LOCATE() search match
   * LogListingRepository so deleting honours the same rows the listing shows.
   *
   * @param array{from?: string, to?: string, name?: string[], level?: int[]} $filter
   * @return array{0: string, 1: array<string, mixed>, 2: array<string, int>}
   */
  private function buildFilterSql(array $filter, ?string $search): array {
    $conditions = [];
    $parameters = [];
    $types = [];

    if (!empty($filter['from'])) {
      $conditions[] = '`created_at` >= :date_from';
      $parameters['date_from'] = $filter['from'] . ' 00:00:00';
      $types['date_from'] = ParameterType::STRING;
    }
    if (!empty($filter['to'])) {
      $conditions[] = '`created_at` <= :date_to';
      $parameters['date_to'] = $filter['to'] . ' 23:59:59';
      $types['date_to'] = ParameterType::STRING;
    }
    if (!empty($filter['name'])) {
      $conditions[] = '`name` IN (:names)';
      $parameters['names'] = array_values($filter['name']);
      $types['names'] = ArrayParameterType::STRING;
    }
    if (!empty($filter['level'])) {
      $conditions[] = '`level` IN (:levels)';
      $parameters['levels'] = array_values($filter['level']);
      $types['levels'] = ArrayParameterType::INTEGER;
    }
    if ($search !== null && trim($search) !== '') {
      $conditions[] = '(LOCATE(:search, `name`) > 0 OR LOCATE(:search, `message`) > 0)';
      $parameters['search'] = trim($search);
      $types['search'] = ParameterType::STRING;
    }

    return [
      $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
      $parameters,
      $types,
    ];
  }
}
