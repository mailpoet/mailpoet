<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Utils\Json;

class Workflow {
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';
  public const STATUS_DRAFT = 'draft';

  /** @var int */
  private $id;

  /** @var int */
  private $versionId;

  /** @var string */
  private $name;

  /** @var string */
  private $status = self::STATUS_DRAFT;

  /** @var DateTimeImmutable */
  private $createdAt;

  /** @var DateTimeImmutable */
  private $updatedAt;

  /** @var array<string, Step> */
  private $steps;

  /** @param Step[] $steps */
  public function __construct(
    string $name,
    array $steps,
    int $id = null,
    int $versionId = null
  ) {
    $this->name = $name;
    $this->steps = [];
    foreach ($steps as $step) {
      $this->steps[$step->getId()] = $step;
    }

    if ($id) {
      $this->id = $id;
    }
    if ($versionId) {
      $this->versionId = $versionId;
    }

    $now = new DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  public function getId(): int {
    return $this->id;
  }

  public function getVersionId(): int {
    return $this->versionId;
  }

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function setStatus(string $status): void {
    $this->status = $status;
  }

  public function getCreatedAt(): DateTimeImmutable {
    return $this->createdAt;
  }

  public function getUpdatedAt(): DateTimeImmutable {
    return $this->updatedAt;
  }

  /** @return array<string, Step> */
  public function getSteps(): array {
    return $this->steps;
  }

  /** @param array<string, Step> $steps */
  public function setSteps(array $steps): void {
    $this->steps = $steps;
  }

  public function getStep(string $id): ?Step {
    return $this->steps[$id] ?? null;
  }

  public function getTrigger(string $key): ?Step {
    foreach ($this->steps as $step) {
      if ($step->getType() === Step::TYPE_TRIGGER && $step->getKey() === $key) {
        return $step;
      }
    }
    return null;
  }

  public function toArray(): array {
    return [
      'name' => $this->name,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $this->updatedAt->format(DateTimeImmutable::W3C),
      'steps' => Json::encode(
        array_map(function (Step $step) {
          return $step->toArray();
        }, $this->steps)
      ),
      'trigger_keys' => Json::encode(
        array_reduce($this->steps, function (array $triggerKeys, Step $step): array {
          if ($step->getType() === Step::TYPE_TRIGGER) {
            $triggerKeys[] = $step->getKey();
          }
          return $triggerKeys;
        }, [])
      ),
    ];
  }

  public static function fromArray(array $data): self {
    // TODO: validation
    $workflow = new self($data['name'], self::parseSteps(Json::decode($data['steps'])));
    $workflow->id = (int)$data['id'];
    $workflow->versionId = (int)$data['version_id'];
    $workflow->status = $data['status'];
    $workflow->createdAt = new DateTimeImmutable($data['created_at']);
    $workflow->updatedAt = new DateTimeImmutable($data['updated_at']);
    return $workflow;
  }

  private static function parseSteps(array $data): array {
    $steps = [];
    foreach ($data as $step) {
      $steps[] = new Step(
        $step['id'],
        $step['type'],
        $step['key'],
        $step['next_step_id'] ?? null,
        $step['args'] ?? []
      );
    }
    return $steps;
  }
}
