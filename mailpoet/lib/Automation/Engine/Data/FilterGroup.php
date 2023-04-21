<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class FilterGroup {
  public const OPERATOR_AND = 'and';
  public const OPERATOR_OR = 'or';

  /** @var string */
  private $operator;

  /** @var Filter[] */
  private $filters;

  public function __construct(
    string $operator,
    array $filters
  ) {
    $this->operator = $operator;
    $this->filters = $filters;
  }

  public function getOperator(): string {
    return $this->operator;
  }

  public function getFilters(): array {
    return $this->filters;
  }

  public function toArray(): array {
    return [
      'operator' => $this->operator,
      'filters' => array_map(function (Filter $filter): array {
        return $filter->toArray();
      }, $this->filters),
    ];
  }

  public static function fromArray(array $data): self {
    return new self(
      $data['operator'],
      array_map(function (array $filter) {
        return Filter::fromArray($filter);
      }, $data['filters'])
    );
  }
}
