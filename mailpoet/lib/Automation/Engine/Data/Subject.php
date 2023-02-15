<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use MailPoet\Automation\Engine\Utils\Json;

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
      'args' => Json::encode($this->getArgs()),
    ];
  }

  public static function fromArray(array $data): self {
    return new self($data['key'], Json::decode($data['args']));
  }
}
