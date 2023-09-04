<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use MailPoet\Automation\Engine\Utils\Json;
use Throwable;

class AutomationRunLog {
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETE = 'complete';
  public const STATUS_FAILED = 'failed';

  public const STATUS_ALL = [
    self::STATUS_RUNNING,
    self::STATUS_COMPLETE,
    self::STATUS_FAILED,
  ];

  /** @var int */
  private $id;

  /** @var int */
  private $automationRunId;

  /** @var string */
  private $stepId;

  /** @var string */
  private $status;

  /** @var DateTimeImmutable */
  private $startedAt;

  /** @var DateTimeImmutable|null */
  private $completedAt;

  /** @var array */
  private $data = [];

  /** @var array */
  private $error = [];

  public function __construct(
    int $automationRunId,
    string $stepId,
    int $id = null
  ) {
    $this->automationRunId = $automationRunId;
    $this->stepId = $stepId;
    $this->status = self::STATUS_RUNNING;
    $this->startedAt = new DateTimeImmutable();

    if ($id) {
      $this->id = $id;
    }
  }

  public function getId(): int {
    return $this->id;
  }

  public function getAutomationRunId(): int {
    return $this->automationRunId;
  }

  public function getStepId(): string {
    return $this->stepId;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function setStatus(string $status): void {
    if (!in_array($status, self::STATUS_ALL, true)) {
      throw new InvalidArgumentException("Invalid status '$status'.");
    }
    $this->status = $status;
  }

  public function getStartedAt(): DateTimeImmutable {
    return $this->startedAt;
  }

  public function getCompletedAt(): ?DateTimeImmutable {
    return $this->completedAt;
  }

  public function setCompletedAt(DateTimeImmutable $completedAt): void {
    $this->completedAt = $completedAt;
  }

  public function getData(): array {
    return $this->data;
  }

  /** @param mixed $value */
  public function setData(string $key, $value): void {
    if (!$this->isDataStorable($value)) {
      throw new InvalidArgumentException("Invalid data provided for key '$key'. Only scalar values and arrays of scalar values are allowed.");
    }
    $this->data[$key] = $value;
  }

  public function getError(): array {
    return $this->error;
  }

  public function toArray(): array {
    return [
      'automation_run_id' => $this->automationRunId,
      'step_id' => $this->stepId,
      'status' => $this->status,
      'started_at' => $this->startedAt->format(DateTimeImmutable::W3C),
      'completed_at' => $this->completedAt ? $this->completedAt->format(DateTimeImmutable::W3C) : null,
      'error' => Json::encode($this->error),
      'data' => Json::encode($this->data),
    ];
  }

  public function setError(Throwable $error): void {
    $this->error = [
      'message' => $error->getMessage(),
      'errorClass' => get_class($error),
      'code' => $error->getCode(),
      'trace' => $error->getTrace(),
    ];
  }

  public static function fromArray(array $data): self {
    $log = new AutomationRunLog((int)$data['automation_run_id'], $data['step_id']);
    $log->id = (int)$data['id'];
    $log->status = $data['status'];
    $log->startedAt = new DateTimeImmutable($data['started_at']);
    $log->completedAt = $data['completed_at'] ? new DateTimeImmutable($data['completed_at']) : null;
    $log->data = Json::decode($data['data']);
    $log->error = Json::decode($data['error']);
    return $log;
  }

  /** @param mixed $data */
  private function isDataStorable($data): bool {
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
