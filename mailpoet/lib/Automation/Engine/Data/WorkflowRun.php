<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Utils\Json;

class WorkflowRun {
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETE = 'complete';
  public const STATUS_CANCELLED = 'cancelled';
  public const STATUS_FAILED = 'failed';

  /** @var int */
  private $id;

  /** @var int */
  private $workflowId;

  /** @var int */
  private $versionId;

  /** @var string */
  private $triggerKey;

  /** @var string */
  private $status = self::STATUS_RUNNING;

  /** @var DateTimeImmutable */
  private $createdAt;

  /** @var DateTimeImmutable */
  private $updatedAt;

  /** @var Subject[] */
  private $subjects;

  /**
   * @param Subject[] $subjects
   */
  public function __construct(
    int $workflowId,
    int $versionId,
    string $triggerKey,
    array $subjects,
    int $id = null
  ) {
    $this->workflowId = $workflowId;
    $this->versionId = $versionId;
    $this->triggerKey = $triggerKey;
    $this->subjects = $subjects;

    if ($id) {
      $this->id = $id;
    }

    $now = new DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  public function getId(): int {
    return $this->id;
  }

  public function getWorkflowId(): int {
    return $this->workflowId;
  }

  public function getVersionId(): int {
    return $this->versionId;
  }

  public function getTriggerKey(): string {
    return $this->triggerKey;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getCreatedAt(): DateTimeImmutable {
    return $this->createdAt;
  }

  public function getUpdatedAt(): DateTimeImmutable {
    return $this->updatedAt;
  }

  /** @return Subject[] */
  public function getSubjects(string $key = null): array {
    if ($key) {
      return array_values(
        array_filter($this->subjects, function (Subject $subject) use ($key) {
          return $subject->getKey() === $key;
        })
      );
    }
    return $this->subjects;
  }

  public function toArray(): array {
    return [
      'workflow_id' => $this->workflowId,
      'version_id' => $this->versionId,
      'trigger_key' => $this->triggerKey,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $this->updatedAt->format(DateTimeImmutable::W3C),
      'subjects' => Json::encode(
        array_map(function (Subject $subject): array {
          return $subject->toArray();
        }, $this->subjects)
      ),
    ];
  }

  public static function fromArray(array $data): self {
    $workflowRun = new WorkflowRun(
      (int)$data['workflow_id'],
      (int)$data['version_id'],
      $data['trigger_key'],
      array_map(function (array $subject) {
        return Subject::fromArray($subject);
      }, Json::decode($data['subjects']))
    );
    $workflowRun->id = (int)$data['id'];
    $workflowRun->status = $data['status'];
    $workflowRun->createdAt = new DateTimeImmutable($data['created_at']);
    $workflowRun->updatedAt = new DateTimeImmutable($data['updated_at']);
    return $workflowRun;
  }
}
