<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use ActionScheduler_Store;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Control\ActionScheduler as ASWrapper;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Validator\Builder;

class UpdateRunStatusEndpoint extends Endpoint {

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var ASWrapper */
  private $actionScheduler;

  public function __construct(
    AutomationRunStorage $automationRunStorage,
    ASWrapper $actionScheduler
  ) {
    $this->automationRunStorage = $automationRunStorage;
    $this->actionScheduler = $actionScheduler;
  }

  public function handle(Request $request): Response {
    /** @var int $runId */
    $runId = $request->getParam('id');
    $runId = intval($runId);

    /** @var string|null $status */
    $status = $request->getParam('status');

    $run = $this->automationRunStorage->getAutomationRun($runId);
    if (!$run) {
      throw Exceptions::automationRunNotFound($runId);
    }

    $currentStatus = $run->getStatus();
    $targetStatus = $status;

    // Validate allowed status values
    $allowedStatuses = [
      AutomationRun::STATUS_RUNNING,
      AutomationRun::STATUS_CANCELLED,
    ];
    if (!in_array($targetStatus, $allowedStatuses, true)) {
      throw UnexpectedValueException::create()
        ->withMessage(__('Invalid status value.', 'mailpoet'))
        ->withErrors(['status' => __('Status must be "running" or "cancelled".', 'mailpoet')]);
    }

    // Validate status transitions
    if ($currentStatus === $targetStatus) {
      // Same status, no change needed
      return new Response([
        'id' => $run->getId(),
        'status' => $run->getStatus(),
        'updated_at' => $run->getUpdatedAt()->format(\DateTimeImmutable::W3C),
      ]);
    }

    // Allow transitions: running → cancelled, cancelled → running
    $allowedTransitions = [
      AutomationRun::STATUS_RUNNING => [AutomationRun::STATUS_CANCELLED],
      AutomationRun::STATUS_CANCELLED => [AutomationRun::STATUS_RUNNING],
    ];

    if (
      !isset($allowedTransitions[$currentStatus]) ||
      !in_array($targetStatus, $allowedTransitions[$currentStatus], true)
    ) {
      throw UnexpectedValueException::create()
        ->withMessage(
          sprintf(
            // translators: This is an error message for an invalid status transition for an automation run. %1$s is the current status, %2$s is the target status.
            __('Cannot transition run from "%1$s" to "%2$s".', 'mailpoet'),
            $currentStatus,
            $targetStatus
          )
        )
        ->withErrors(['status' => __('Invalid status transition.', 'mailpoet')]);
    }

    // Update run status
    $this->automationRunStorage->updateStatus($runId, $targetStatus);

    // Schedule/unschedule actions based on status change
    if ($targetStatus === AutomationRun::STATUS_CANCELLED) {
      $this->unschedulePendingForRun($runId);
    } elseif ($targetStatus === AutomationRun::STATUS_RUNNING) {
      // If nothing pending for this run, enqueue next step based on stored next_step_id
      if (!$this->hasPendingForRun($runId)) {
        $nextStepId = $this->automationRunStorage->getNextStepId($runId);
        if ($nextStepId) {
          $this->enqueueStep($runId, (string)$nextStepId, 1);
        }
      }
    }

    // Get updated run
    $updatedRun = $this->automationRunStorage->getAutomationRun($runId);
    if (!$updatedRun) {
      throw Exceptions::automationRunNotFound($runId);
    }

    // Return updated run data
    return new Response([
      'id' => $updatedRun->getId(),
      'status' => $updatedRun->getStatus(),
      'updated_at' => $updatedRun->getUpdatedAt()->format(\DateTimeImmutable::W3C),
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'status' => Builder::string()->required(),
    ];
  }

  private function unschedulePendingForRun(int $runId): void {
    $actions = $this->actionScheduler->getScheduledActions([
      'hook' => Hooks::AUTOMATION_STEP,
      'status' => ActionScheduler_Store::STATUS_PENDING,
    ]);
    foreach ($actions as $action) {
      $args = $action->get_args();
      if (is_array($args) && isset($args[0]['automation_run_id']) && (int)$args[0]['automation_run_id'] === $runId) {
        $this->actionScheduler->unscheduleAction(Hooks::AUTOMATION_STEP, $args);
      }
    }
  }

  private function hasPendingForRun(int $runId): bool {
    $actions = $this->actionScheduler->getScheduledActions([
      'hook' => Hooks::AUTOMATION_STEP,
      'status' => ActionScheduler_Store::STATUS_PENDING,
    ]);
    foreach ($actions as $action) {
      $args = $action->get_args();
      if (is_array($args) && isset($args[0]['automation_run_id']) && (int)$args[0]['automation_run_id'] === $runId) {
        return true;
      }
    }
    return false;
  }

  private function enqueueStep(int $runId, string $stepId, int $runNumber = 1): int {
    return $this->actionScheduler->enqueue(Hooks::AUTOMATION_STEP, [[
      'automation_run_id' => $runId,
      'step_id' => $stepId,
      'run_number' => $runNumber,
    ]]);
  }
}
