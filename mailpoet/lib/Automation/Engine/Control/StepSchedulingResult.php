<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

class StepSchedulingResult {
  public const STATUS_SCHEDULED = 'scheduled';
  public const STATUS_COMPLETED_NO_NEXT_STEP = 'completed_no_next_step';
  public const STATUS_ENQUEUE_FAILED = 'enqueue_failed';

  /** @var string */
  private $status;

  /** @var int|null */
  private $actionId;

  /** @var string|null */
  private $nextStepId;

  private function __construct(
    string $status,
    ?int $actionId = null,
    ?string $nextStepId = null
  ) {
    $this->status = $status;
    $this->actionId = $actionId;
    $this->nextStepId = $nextStepId;
  }

  public static function scheduled(int $actionId, string $nextStepId): self {
    return new self(self::STATUS_SCHEDULED, $actionId, $nextStepId);
  }

  public static function completedNoNextStep(): self {
    return new self(self::STATUS_COMPLETED_NO_NEXT_STEP);
  }

  public static function enqueueFailed(string $nextStepId): self {
    return new self(self::STATUS_ENQUEUE_FAILED, null, $nextStepId);
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getActionId(): ?int {
    return $this->actionId;
  }

  public function getNextStepId(): ?string {
    return $this->nextStepId;
  }

  public function isEnqueueFailed(): bool {
    return $this->status === self::STATUS_ENQUEUE_FAILED;
  }
}
