<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class Subject {
  /** @var string */
  private $key;

  /** @var array */
  private $args;

  public function __construct(
    string $key,
    array $args
  ) {
    $this->key = $key;
    $this->args = $args;
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getArgs(): array {
    return $this->args;
  }

  public function toArray(): array {
    return [
      'key' => $this->getKey(),
      'args' => $this->getArgs(),
    ];
  }

  public static function fromArray(array $data): self {
    return new self($data['key'], $data['args']);
  }
}
