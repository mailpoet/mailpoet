<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
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
  private $createdAt;

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

  /** @var array */
  private $args;

  public function __construct(
    int $workflowRunId,
    string $stepId,
    array $args,
    int $id = null
  ) {
    $this->workflowRunId = $workflowRunId;
    $this->stepId = $stepId;
    $this->args = $args;
    $this->status = self::STATUS_RUNNING;

    if ($id) {
      $this->id = $id;
    }

    $now = new DateTimeImmutable();
    $this->createdAt = $now;

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

  public function getArgs(): array {
    return $this->args;
  }

  public function getError(): array {
    return $this->error;
  }

  public function getData(): array {
    return $this->data;
  }

  /**
   * @return DateTimeImmutable|null
   */
  public function getCompletedAt() {
    return $this->completedAt;
  }

  /**
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public function setData(string $key, $value): void {
    try {
      $newData = $this->getData();
      $newData[$key] = $value;
      $encoded = Json::encode($newData);
      $decoded = Json::decode($encoded);
      if ($decoded !== $newData) {
        throw new InvalidArgumentException('$value must be serializable');
      }
    } catch (InvalidStateException | InvalidArgumentException | UnexpectedValueException $e) {
      throw new InvalidArgumentException("Invalid data provided for key $key.");
    }
    $this->data[$key] = $value;
  }

  public function getCreatedAt(): DateTimeImmutable {
    return $this->createdAt;
  }

  public function toArray(): array {
    return [
      'workflow_run_id' => $this->workflowRunId,
      'step_id' => $this->stepId,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'completed_at' => $this->completedAt ? $this->completedAt->format(DateTimeImmutable::W3C) : null,
      'args' => Json::encode($this->args),
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
    $workflowRunLog = new WorkflowRunLog((int)$data['workflow_run_id'], $data['step_id'], []);
    $workflowRunLog->id = (int)$data['id'];
    $workflowRunLog->status = $data['status'];
    $workflowRunLog->error = Json::decode($data['error']);
    $workflowRunLog->data = Json::decode($data['data']);
    $workflowRunLog->args = Json::decode($data['args']);
    $workflowRunLog->createdAt = new DateTimeImmutable($data['created_at']);

    if ($data['completed_at']) {
      $workflowRunLog->completedAt = new DateTimeImmutable($data['completed_at']);
    }

    return $workflowRunLog;
  }
}
