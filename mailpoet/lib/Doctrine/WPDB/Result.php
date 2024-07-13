<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoetVendor\Doctrine\DBAL\Driver\Result as ResultInterface;

/**
 * WPDB fetches all results from the underlying database driver,
 * so we need to implement the result methods on in-memory data.
 */
class Result implements ResultInterface {
  /** @var array[] */
  private array $result = [];
  private int $rowCount;
  private int $cursor = 0;

  public function __construct(
    array $result,
    int $rowCount
  ) {
    foreach ($result as $value) {
      $this->result[] = (array)$value;
    }
    $this->rowCount = $rowCount;
  }

  public function fetchNumeric() {
    $value = $this->result[$this->cursor++] ?? null;
    return $value === null ? false : array_values($value);
  }

  public function fetchAssociative() {
    return $this->result[$this->cursor++] ?? false;
  }

  public function fetchOne() {
    $value = $this->result[$this->cursor++] ?? null;
    return $value === null ? false : reset($value);
  }

  public function fetchAllNumeric(): array {
    $result = [];
    foreach ($this->result as $value) {
      $result[] = array_values($value);
    }
    return $result;
  }

  public function fetchAllAssociative(): array {
    return $this->result;
  }

  public function fetchFirstColumn(): array {
    $result = [];
    foreach ($this->result as $value) {
      $result[] = reset($value);
    }
    return $result;
  }

  public function rowCount(): int {
    return $this->rowCount;
  }

  public function columnCount(): int {
    return count($this->result[0] ?? []);
  }

  public function free(): void {
    $this->cursor = 0;
  }
}
