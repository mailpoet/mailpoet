<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use MailPoet\Automation\Engine\Utils\Json;
use Throwable;

class WorkflowRunLog {

  const STATUS_RUNNING = 'running';
  const STATUS_COMPLETED = 'completed';
  const STATUS_FAILED = 'failed';

  /** @var int */
  private $id;

  /** @var int */
  private $workflowRunId;

  /** @var DateTimeImmutable */
  private $startedAt;

  /** @var DateTimeImmutable|null */
  private $completedAt;

  /** @var string */
  private $status;

  /** @var array */
  private $error;

  /** @var array */
  private $data;

  /** @var string */
  private $stepId;

  public function __construct(
    int $workflowRunId,
    string $stepId,
    int $id = null
  ) {
    $this->workflowRunId = $workflowRunId;
    $this->stepId = $stepId;
    $this->status = self::STATUS_RUNNING;

    if ($id) {
      $this->id = $id;
    }

    $this->startedAt = new DateTimeImmutable();

    $this->error = [];
    $this->data = [];
  }

  public function getId(): int {
    return $this->id;
  }

  public function getWorkflowRunId(): int {
    return $this->workflowRunId;
  }

  public function getStepId(): string {
    return $this->stepId;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getError(): array {
    return $this->error;
  }

  public function getData(): array {
    return $this->data;
  }

  public function getCompletedAt(): ?DateTimeImmutable {
    return $this->completedAt;
  }

  /**
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public function setData(string $key, $value): void {
    if (!$this->isDataStorable($value)) {
      throw new InvalidArgumentException("Invalid data provided for key '$key'. Only scalar values and arrays of scalar values are allowed.");
    }
    $this->data[$key] = $value;
  }

  public function getStartedAt(): DateTimeImmutable {
    return $this->startedAt;
  }

  public function toArray(): array {
    return [
      'workflow_run_id' => $this->workflowRunId,
      'step_id' => $this->stepId,
      'status' => $this->status,
      'started_at' => $this->startedAt->format(DateTimeImmutable::W3C),
      'completed_at' => $this->completedAt ? $this->completedAt->format(DateTimeImmutable::W3C) : null,
      'error' => Json::encode($this->error),
      'data' => Json::encode($this->data),
    ];
  }

  public function markCompletedSuccessfully(): void {
    $this->status = self::STATUS_COMPLETED;
    $this->completedAt = new DateTimeImmutable();
  }

  public function markFailed(): void {
    $this->status = self::STATUS_FAILED;
    $this->completedAt = new DateTimeImmutable();
  }

  public function setError(Throwable $error): void {
    $error = [
      'message' => $error->getMessage(),
      'errorClass' => get_class($error),
      'code' => $error->getCode(),
      'trace' => $error->getTrace(),
    ];

    $this->error = $error;
  }

  public static function fromArray(array $data): self {
    $workflowRunLog = new WorkflowRunLog((int)$data['workflow_run_id'], $data['step_id']);
    $workflowRunLog->id = (int)$data['id'];
    $workflowRunLog->status = $data['status'];
    $workflowRunLog->error = Json::decode($data['error']);
    $workflowRunLog->data = Json::decode($data['data']);
    $workflowRunLog->startedAt = new DateTimeImmutable($data['started_at']);

    if ($data['completed_at']) {
      $workflowRunLog->completedAt = new DateTimeImmutable($data['completed_at']);
    }

    return $workflowRunLog;
  }

  /**
   * @param mixed $data
   * @return bool
   */
  private function isDataStorable($data): bool {
    if (is_object($data)) {
      return false;
    }

    if (is_scalar($data)) {
      return true;
    }

    if (!is_array($data)) {
      return false;
    }

    foreach ($data as $value) {
      if (!$this->isDataStorable($value)) {
        return false;
      }
    }

    return true;
  }
}
