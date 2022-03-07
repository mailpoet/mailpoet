<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use DateTimeImmutable;

class WorkflowRun {
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETE = 'complete';
  public const STATUS_CANCELLED = 'cancelled';
  public const STATUS_FAILED = 'failed';

  /** @var int */
  private $id;

  /** @var int */
  private $workflowId;

  /** @var string */
  private $triggerKey;

  /** @var string */
  private $status = self::STATUS_RUNNING;

  /** @var DateTimeImmutable */
  private $createdAt;

  /** @var DateTimeImmutable */
  private $updatedAt;

  public function __construct(
    int $workflowId,
    string $triggerKey
  ) {
    $this->workflowId = $workflowId;
    $this->triggerKey = $triggerKey;

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

  public function toArray(): array {
    return [
      'workflow_id' => $this->workflowId,
      'trigger_key' => $this->triggerKey,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $this->updatedAt->format(DateTimeImmutable::W3C),
    ];
  }

  public static function fromArray(array $data): self {
    $workflowRun = new WorkflowRun((int)$data['workflow_id'], $data['trigger_key']);
    $workflowRun->id = (int)$data['id'];
    $workflowRun->status = $data['status'];
    $workflowRun->createdAt = $data['created_at'];
    $workflowRun->createdAt = $data['updated_at'];
    return $workflowRun;
  }
}
