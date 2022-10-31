<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

interface Logger {
  /** @param array{name: string, status: string, started_at: string|null, completed_at: string|null, retries: int|null, error: string|null}[] $status */
  public function logBefore(array $status): void;

  /** @param array{name: string, status: string, started_at: string|null, completed_at: string|null, retries: int|null, error: string|null} $migration */
  public function logMigrationStarted(array $migration): void;

  /** @param array{name: string, status: string, started_at: string|null, completed_at: string|null, retries: int|null, error: string|null} $migration */
  public function logMigrationCompleted(array $migration): void;

  public function logAfter(): void;
}
