<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

class DataInconsistencyController {
  const ORPHANED_TASKS = 'orphaned_tasks';

  private DataInconsistencyRepository $repository;

  public function __construct(
    DataInconsistencyRepository $repository
  ) {
    $this->repository = $repository;
  }

  public function getInconsistentDataStatus(): array {
    $result = [
      self::ORPHANED_TASKS => $this->repository->getOrphanedSendingTasksCount(),
    ];
    $result['total'] = array_sum($result);
    return $result;
  }
}
