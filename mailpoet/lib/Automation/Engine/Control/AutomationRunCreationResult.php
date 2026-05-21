<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

class AutomationRunCreationResult {
  public const STATUS_CREATED = 'created';
  public const STATUS_TRIGGER_FILTER_MISMATCH = 'trigger_filter_mismatch';
  public const STATUS_RUN_CREATE_HOOK_REJECTED = 'run_create_hook_rejected';
  public const STATUS_STEP_SCHEDULING_FAILED = 'step_scheduling_failed';

  /** @var string */
  private $status;

  /** @var int|null */
  private $automationRunId;

  /** @var StepSchedulingResult|null */
  private $schedulingResult;

  private function __construct(
    string $status,
    ?int $automationRunId = null,
    ?StepSchedulingResult $schedulingResult = null
  ) {
    $this->status = $status;
    $this->automationRunId = $automationRunId;
    $this->schedulingResult = $schedulingResult;
  }

  public static function created(int $automationRunId, StepSchedulingResult $schedulingResult): self {
    return new self(self::STATUS_CREATED, $automationRunId, $schedulingResult);
  }

  public static function skipped(string $status): self {
    return new self($status);
  }

  public static function schedulingFailed(int $automationRunId, StepSchedulingResult $schedulingResult): self {
    return new self(self::STATUS_STEP_SCHEDULING_FAILED, $automationRunId, $schedulingResult);
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getAutomationRunId(): ?int {
    return $this->automationRunId;
  }

  public function getSchedulingResult(): ?StepSchedulingResult {
    return $this->schedulingResult;
  }

  public function isCreated(): bool {
    return $this->status === self::STATUS_CREATED;
  }
}
