<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

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

  public function __construct(
    int $workflowId,
    string $triggerKey,
    array $subjects
  ) {
    $this->workflowId = $workflowId;
    $this->triggerKey = $triggerKey;
    $this->subjects = $subjects;

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

  /** @return Subject[] */
  public function getSubjects(): array {
    return $this->subjects;
  }

  public function toArray(): array {
    return [
      'workflow_id' => $this->workflowId,
      'trigger_key' => $this->triggerKey,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $this->updatedAt->format(DateTimeImmutable::W3C),
      'subjects' => Json::encode(
        array_reduce($this->subjects, function (array $subjects, Subject $subject): array {
          $subjects[$subject->getKey()] = $subject->pack();
          return $subjects;
        }, [])
      ),
    ];
  }

  public static function fromArray(array $data): self {
    $workflowRun = new WorkflowRun((int)$data['workflow_id'], $data['trigger_key'], Json::decode($data['subjects']));
    $workflowRun->id = (int)$data['id'];
    $workflowRun->status = $data['status'];
    $workflowRun->createdAt = $data['created_at'];
    $workflowRun->createdAt = $data['updated_at'];
    return $workflowRun;
  }
}
