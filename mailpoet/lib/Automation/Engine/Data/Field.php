<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class Field {
  public const TYPE_BOOLEAN = 'boolean';
  public const TYPE_INTEGER = 'integer';
  public const TYPE_STRING = 'string';
  public const TYPE_ENUM = 'enum';

  /** @var string */
  private $key;

  /** @var string */
  private $type;

  /** @var string */
  private $name;

  /** @var callable */
  private $factory;

  /** @var array */
  private $args;

  public function __construct(
    string $key,
    string $type,
    string $name,
    callable $factory,
    array $args = []
  ) {

    $this->key = $key;
    $this->type = $type;
    $this->name = $name;
    $this->factory = $factory;
    $this->args = $args;
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getType(): string {
    return $this->type;
  }

  public function getName(): string {
    return $this->name;
  }

  public function getFactory(): callable {
    return $this->factory;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->getFactory()();
  }

  public function getArgs(): array {
    return $this->args;
  }
}
