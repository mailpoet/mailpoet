<?php declare (strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\MissingParameterException;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\SQL\Parser\Visitor;

class ConvertParameters implements Visitor {
  private const PARAM_TYPE_MAP = [
    ParameterType::STRING => '%s',
    ParameterType::INTEGER => '%d',
    ParameterType::ASCII => '%s',
    ParameterType::BINARY => '%s',
    ParameterType::BOOLEAN => '%d',
    ParameterType::NULL => '%s',
    ParameterType::LARGE_OBJECT => '%s',
  ];

  /** @var list<string> */
  private array $buffer = [];

  /** @var array<array-key, array{0: string, 1: mixed, 2: int}> */
  private array $params;

  private array $values = [];

  private int $cursor = 1;

  public function __construct(
    array $params
  ) {
    $this->params = $params;
  }

  public function acceptPositionalParameter(string $sql): void {
    $position = $this->cursor++;
    $this->acceptParameter($position);
  }

  public function acceptNamedParameter(string $sql): void {
    $this->acceptParameter(trim($sql, ':'));
  }

  public function acceptOther(string $sql): void {
    $this->buffer[] = $sql;
  }

  public function getSQL(): string {
    return implode('', $this->buffer);
  }

  public function getValues(): array {
    return $this->values;
  }

  /** @param array-key $key */
  private function acceptParameter($key): void {
    if (!array_key_exists($key, $this->params)) {
      throw new MissingParameterException(sprintf("Parameter '%s' was defined in the query, but not provided.", $key));
    }
    [, $value, $type] = $this->params[$key];

    // WPDB doesn't support NULL values. We need to handle them explicitly.
    if ($value === null) {
      $this->buffer[] = 'NULL';
      return;
    }

    // WPDB doesn't accept non-scalar values. We need to cast them (PDO-like behavior).
    if (!is_scalar($value)) {
      if ($type === ParameterType::INTEGER) {
        $value = (int)$value; // @phpstan-ignore-line -- cast may fail and that's OK
      } elseif ($type === ParameterType::BOOLEAN) {
        $value = (bool)$value;
      } else {
        $value = (string)$value; // @phpstan-ignore-line -- cast may fail and that's OK
      }
    }

    $this->values[] = $value;
    $this->buffer[] = self::PARAM_TYPE_MAP[$type] ?? '%s';
  }
}
