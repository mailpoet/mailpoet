<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use WP_CLI;

class Cli {
  /** @var Migrator */
  private $migrator;

  /** @var Repository */
  private $repository;

  /** @var Store */
  private $store;

  public function __construct(
    Migrator $migrator,
    Repository $repository,
    Store $store
  ) {
    $this->migrator = $migrator;
    $this->repository = $repository;
    $this->store = $store;
  }

  public function initialize(): void {
    if (!class_exists(WP_CLI::class)) {
      return;
    }

    WP_CLI::add_command('mailpoet:migrations:run', [$this->migrator, 'run'], [
      'shortdesc' => 'Runs MailPoet database migrations',
    ]);

    WP_CLI::add_command('mailpoet:migrations:status', [$this, 'status'], [
      'shortdesc' => 'Shows status of MailPoet database migrations',
    ]);
  }

  public function status(): void {
    $this->printHeader();
    $status = $this->migrator->getStatus();
    if (!$status) {
      WP_CLI::warning("No migrations found.\n");
    } else {
      WP_CLI::log("STATUS:\n");
      $this->printStats($status);

      WP_CLI::log("MIGRATIONS:\n");
      $table = array_map(function (array $data): array {
        return array_map(function ($field) {
          return $field === null ? '' : $field;
        }, $data);
      }, $status);
      WP_CLI\Utils\format_items('table', $table, array_keys($table[0]));
    }
  }

  public function printHeader(): void {
    WP_CLI::log('MAILPOET DATABASE MIGRATIONS');
    WP_CLI::log("============================\n");
  }

  public function printStats(array $status): void {
    $stats = [
      Migrator::MIGRATION_STATUS_NEW => 0,
      Migrator::MIGRATION_STATUS_COMPLETED => 0,
      Migrator::MIGRATION_STATUS_STARTED => 0,
      Migrator::MIGRATION_STATUS_FAILED => 0,
    ];
    foreach ($status as $migration) {
      $stats[$migration['status']] += 1;
    }

    $defined = count($this->repository->loadAll());
    $processed = array_sum($stats) - $stats[Migrator::MIGRATION_STATUS_NEW];

    WP_CLI::log(sprintf('Defined:    %4d  (in %s)', $defined, realpath($this->repository->getMigrationsDir())));
    WP_CLI::log(sprintf('Processed:  %4d  (in database table \'%s\')', $processed, $this->store->getMigrationsTable()));
    WP_CLI::log('');
    WP_CLI::log(sprintf('New:        %4d  (not run yet)', $stats[Migrator::MIGRATION_STATUS_NEW]));
    WP_CLI::log(sprintf('Completed:  %4d  (successfully executed)', $stats[Migrator::MIGRATION_STATUS_COMPLETED]));
    WP_CLI::log(sprintf('Started:    %4d  (still running, or never completed)', $stats[Migrator::MIGRATION_STATUS_STARTED]));
    WP_CLI::log(sprintf('Failed:     %4d  (an error occurred)', $stats[Migrator::MIGRATION_STATUS_FAILED]));
    WP_CLI::log('');
  }
}
