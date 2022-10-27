<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

class Migrator {
  const MIGRATION_STATUS_NEW = 'new';
  const MIGRATION_STATUS_STARTED = 'started';
  const MIGRATION_STATUS_COMPLETED = 'completed';
  const MIGRATION_STATUS_FAILED = 'failed';

  /** @var Repository */
  private $repository;

  /** @var Store */
  private $store;

  public function __construct(
    Repository $repository,
    Store $store
  ) {
    $this->repository = $repository;
    $this->store = $store;
  }

  /** @return array{name: string, status: string, started_at: string|null, completed_at: string|null, error: string|null}[] */
  public function getStatus(): array {
    $defined = $this->repository->loadAll();
    $processed = $this->store->getAll();
    $processedMap = array_combine(array_column($processed, 'name'), $processed) ?: [];
    $all = array_unique(array_merge($defined, array_keys($processedMap)));
    sort($all);

    $status = [];
    foreach ($all as $name) {
      $data = $processedMap[$name] ?? [];
      $status[] = [
        'name' => $name,
        'status' => $data ? $this->getMigrationStatus($data) : self::MIGRATION_STATUS_NEW,
        'started_at' => $data['started_at'] ?? null,
        'completed_at' => $data['completed_at'] ?? null,
        'error' => $data && $data['error'] ? mb_strimwidth($data['error'], 0, 20, '…') : null,
      ];
    }
    return $status;
  }

  private function getMigrationStatus(array $data): string {
    if (!isset($data['completed_at'])) {
      return self::MIGRATION_STATUS_STARTED;
    }
    return isset($data['error']) ? self::MIGRATION_STATUS_FAILED : self::MIGRATION_STATUS_COMPLETED;
  }
}
