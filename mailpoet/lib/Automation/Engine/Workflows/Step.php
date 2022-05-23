<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

class Step {
  public const TYPE_TRIGGER = 'trigger';
  public const TYPE_ACTION = 'action';

  /** @var string */
  private $id;

  /** @var string */
  private $type;

  /** @var string */
  private $key;

  /** @var string|null */
  private $nextStepId;

  /** @var array */
  private $args;

  public function __construct(
    string $id,
    string $type,
    string $key,
    ?string $nextStepId = null,
    array $args = []
  ) {
    $this->id = $id;
    $this->type = $type;
    $this->key = $key;
    $this->nextStepId = $nextStepId;
    $this->args = $args;
  }

  public function getId(): string {
    return $this->id;
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
      'type' => $this->type,
      'key' => $this->key,
      'next_step_id' => $this->nextStepId,
      'args' => $this->args,
    ];
  }
}
