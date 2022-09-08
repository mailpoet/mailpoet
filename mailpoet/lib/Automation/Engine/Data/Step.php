<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

class Step {
  public const TYPE_TRIGGER = 'trigger';
  public const TYPE_ACTION = 'action';

  /** @var string */
  private $id;

  /** @var string */
  private $type;

  /** @var string */
  private $key;

  /** @var array */
  protected $args;

  /** @var NextStep[] */
  protected $nextSteps;

  /**
   * @param array<string, mixed> $args
   * @param NextStep[] $nextSteps
   */
  public function __construct(
    string $id,
    string $type,
    string $key,
    array $args,
    array $nextSteps
  ) {
    $this->id = $id;
    $this->type = $type;
    $this->key = $key;
    $this->args = $args;
    $this->nextSteps = $nextSteps;
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

  /** @return NextStep[] */
  public function getNextSteps(): array {
    return $this->nextSteps;
  }

  /** @param NextStep[] $nextSteps */
  public function setNextSteps(array $nextSteps): void {
    $this->nextSteps = $nextSteps;
  }

  public function getArgs(): array {
    return $this->args;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'type' => $this->type,
      'key' => $this->key,
      'args' => $this->args,
      'next_steps' => array_map(function (NextStep $nextStep) {
        return $nextStep->toArray();
      }, $this->nextSteps),
    ];
  }

  public static function fromArray(array $data): self {
    return new self(
      $data['id'],
      $data['type'],
      $data['key'],
      $data['args'],
      array_map(function (array $nextStep) {
        return NextStep::fromArray($nextStep);
      }, $data['next_steps'])
    );
  }
}
