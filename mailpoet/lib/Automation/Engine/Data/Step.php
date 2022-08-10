<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class Step {
  public const TYPE_TRIGGER = 'trigger';
  public const TYPE_ACTION = 'action';

  /** @var string */
  private $id;

  /** @var ?string */
  private $name;

  /** @var string */
  private $type;

  /** @var string */
  private $key;

  /** @var string|null */
  protected $nextStepId;

  /** @var array */
  protected $args;

  public function __construct(
    string $id,
    ?string $name,
    string $type,
    string $key,
    ?string $nextStepId = null,
    array $args = []
  ) {
    $this->id = $id;
    $this->name = $name;
    $this->type = $type;
    $this->key = $key;
    $this->nextStepId = $nextStepId;
    $this->args = $args;
  }

  public function getId(): string {
    return $this->id;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function getType(): string {
    return $this->type;
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getNextStepId(): ?string {
    return $this->nextStepId;
  }

  public function setNextStepId(string $id): void {
    $this->nextStepId = $id;
  }

  public function getArgs(): array {
    return $this->args;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'type' => $this->type,
      'key' => $this->key,
      'next_step_id' => $this->nextStepId,
      'args' => $this->args,
    ];
  }
}
