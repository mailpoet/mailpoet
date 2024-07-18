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
    foreach ($result as $row) {
      $this->result[] = (array)$row;
    }
    $this->rowCount = $rowCount;
  }

  public function fetchNumeric() {
    $row = $this->result[$this->cursor++] ?? null;
    return $row === null ? false : array_values($row);
  }

  public function fetchAssociative() {
    return $this->result[$this->cursor++] ?? false;
  }

  public function fetchOne() {
    $row = $this->result[$this->cursor++] ?? null;
    return $row === null ? false : reset($row);
  }

  public function fetchAllNumeric(): array {
    $result = [];
    foreach ($this->result as $row) {
      $result[] = array_values($row);
    }
    return $result;
  }

  public function fetchAllAssociative(): array {
    return $this->result;
  }

  public function fetchFirstColumn(): array {
    $result = [];
    foreach ($this->result as $row) {
      $result[] = reset($row);
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
