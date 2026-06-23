<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\WorkersFactory;
use ReflectionMethod;

class WorkerTypesCatalog {
  const FIELDS = ['type', 'addable', 'schedule_automatically', 'supports_multiple_instances', 'mailing'];

  /**
   * Mailing worker task types: workers that run their own mailer-driven flow instead of
   * CronWorkerInterface and cannot be created standalone. 'sending' covers both the Scheduler and
   * SendingQueue workers.
   */
  const MAILING_TYPES = [
    SendingQueueWorker::TASK_TYPE,
    StatsNotificationsWorker::TASK_TYPE,
  ];

  /**
   * Factory methods that build mailing workers. They are skipped during introspection: their attributes
   * are static (see MAILING_TYPES) and instantiating the queue worker eagerly resolves the mailer, which
   * fails on an unconfigured site. Mirrors the mailing workers Daemon::getWorkers() lists explicitly.
   */
  const MAILING_FACTORY_METHODS = [
    'createScheduleWorker',
    'createQueueWorker',
    'createStatsNotificationsWorker',
  ];

  private WorkersFactory $workersFactory;

  public function __construct(
    WorkersFactory $workersFactory
  ) {
    $this->workersFactory = $workersFactory;
  }

  /**
   * @return array<int, array{type: string, addable: bool, schedule_automatically: bool, supports_multiple_instances: bool, mailing: bool}>
   */
  public function getRows(): array {
    $rows = [];

    foreach ($this->getStandardWorkers() as $worker) {
      $rows[$worker->getTaskType()] = [
        'type' => $worker->getTaskType(),
        'addable' => true,
        'schedule_automatically' => $worker->scheduleAutomatically(),
        'supports_multiple_instances' => $worker->supportsMultipleInstances(),
        'mailing' => false,
      ];
    }

    foreach (self::MAILING_TYPES as $type) {
      $rows[$type] = [
        'type' => $type,
        'addable' => false,
        'schedule_automatically' => false,
        'supports_multiple_instances' => false,
        'mailing' => true,
      ];
    }

    ksort($rows);

    return array_values($rows);
  }

  /**
   * @return string[]
   */
  public function getTypes(): array {
    return array_column($this->getRows(), 'type');
  }

  /**
   * Task types a user may add from the CLI: the standard CronWorkerInterface workers only. Mailing rows
   * ('sending', 'stats_notification') are created by app flows (a newsletter being sent, a digest
   * scheduling) and have no standalone factory, so they are deliberately excluded. Derived from the
   * standard workers, not WorkersFactory::SIMPLE_WORKER_TYPES (which misleadingly lists the mailing
   * 'stats_notification').
   *
   * @return string[]
   */
  public function getAddableTypes(): array {
    $types = [];
    foreach ($this->getStandardWorkers() as $worker) {
      $types[] = $worker->getTaskType();
    }
    sort($types);
    return $types;
  }

  /**
   * Throws unless $type is a known task type (standard or mailing). Shared by the trigger and run
   * commands, which accept any existing type.
   */
  public function assertValidType(string $type): void {
    $validTypes = $this->getTypes();
    if (!in_array($type, $validTypes, true)) {
      $valid = implode(', ', $validTypes);
      throw new InvalidArgumentException("Unknown task type '{$type}'. Valid types: {$valid}.");
    }
  }

  /**
   * Throws unless $type can be added from the CLI. A known-but-mailing type gets a richer message
   * explaining it is created by app flows; an entirely unknown type is reported as such. Used by the
   * add command, which only accepts standard types.
   */
  public function assertAddableType(string $type): void {
    $addable = $this->getAddableTypes();
    if (in_array($type, $addable, true)) {
      return;
    }

    $allTypes = $this->getTypes();
    if (in_array($type, $allTypes, true)) {
      throw new InvalidArgumentException("Task type '{$type}' cannot be added from the CLI. It is a mailing type created by app flows (a newsletter being sent, a digest scheduling). Addable types: " . implode(', ', $addable) . '.');
    }

    throw new InvalidArgumentException("Unknown task type '{$type}'. Addable types: " . implode(', ', $addable) . '.');
  }

  /**
   * Returns the standard worker instance for a task type, or null for mailing/unknown types.
   * Keeps the run command in lockstep with the factory: it resolves the same instances the
   * catalog introspects, so a new create*Worker() method is runnable without touching this code.
   */
  public function getWorkerByType(string $type): ?CronWorkerInterface {
    foreach ($this->getStandardWorkers() as $worker) {
      if ($worker->getTaskType() === $type) {
        return $worker;
      }
    }
    return null;
  }

  /**
   * Instantiates every standalone worker the factory can build, skipping the mailing ones.
   * This keeps the catalog in lockstep with the factory: a new create*Worker() method appears
   * here automatically. The instanceof check guards against a future non-standard worker slipping in.
   *
   * @return iterable<CronWorkerInterface>
   */
  private function getStandardWorkers(): iterable {
    foreach (get_class_methods($this->workersFactory) as $method) {
      if (strpos($method, 'create') !== 0) {
        continue;
      }
      if (in_array($method, self::MAILING_FACTORY_METHODS, true)) {
        continue;
      }
      if ((new ReflectionMethod($this->workersFactory, $method))->getNumberOfRequiredParameters() > 0) {
        continue;
      }
      $worker = $this->workersFactory->{$method}();
      if ($worker instanceof CronWorkerInterface) {
        yield $worker;
      }
    }
  }
}
