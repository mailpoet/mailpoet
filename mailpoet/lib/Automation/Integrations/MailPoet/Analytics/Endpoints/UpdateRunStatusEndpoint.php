<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Validator\Builder;

class UpdateRunStatusEndpoint extends Endpoint {

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  public function __construct(
    AutomationRunStorage $automationRunStorage
  ) {
    $this->automationRunStorage = $automationRunStorage;
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
}
