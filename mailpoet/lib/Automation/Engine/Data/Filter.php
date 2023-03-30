<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class Filter {
  /** @var string */
  private $fieldType;

  /** @var string */
  private $fieldKey;

  /** @var string */
  private $condition;

  /** @var array */
  private $args;

  public function __construct(
    string $fieldType,
    string $fieldKey,
    string $condition,
    array $args
  ) {
    $this->fieldType = $fieldType;
    $this->fieldKey = $fieldKey;
    $this->condition = $condition;
    $this->args = $args;
  }

  public function getFieldType(): string {
    return $this->fieldType;
  }

  public function getFieldKey(): string {
    return $this->fieldKey;
  }

  public function getCondition(): string {
    return $this->condition;
  }

  public function getArgs(): array {
    return $this->args;
  }

  public function toArray(): array {
    return [
      'field_type' => $this->fieldType,
      'field_key' => $this->fieldKey,
      'condition' => $this->condition,
      'args' => $this->args,
    ];
  }

  public static function fromArray(array $data): self {
    return new self(
      $data['field_type'],
      $data['field_key'],
      $data['condition'],
      $data['args']
    );
  }
}
