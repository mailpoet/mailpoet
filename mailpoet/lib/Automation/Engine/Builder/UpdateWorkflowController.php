<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Workflow;

class UpdateWorkflowController {
  /** @var WorkflowStorage */
  private $storage;

  public function __construct(
    WorkflowStorage $storage
  ) {
    $this->storage = $storage;
  }

  public function updateWorkflow(int $id, array $data): Workflow {
    // TODO: data & workflow validation (trigger existence, graph consistency, etc.)
    // TODO: new revisions when content is changed
    // TODO: validation when status being is changed

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }

    if (array_key_exists('status', $data)) {
      $this->checkWorkflowStatus($data['status']);
      $workflow->setStatus($data['status']);
      $this->storage->updateWorkflow($workflow);
    }

    $workflow = $this->storage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }
    return $workflow;
  }

  private function checkWorkflowStatus(string $status): void {
    if (!in_array($status, [Workflow::STATUS_ACTIVE, Workflow::STATUS_INACTIVE, Workflow::STATUS_DRAFT], true)) {
      throw UnexpectedValueException::create()->withMessage(__(sprintf('Invalid status: %s', $status), 'mailpoet'));
    }
  }
}
