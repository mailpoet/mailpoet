<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;

class CreateWorkflowController {
  /** @var WorkflowStorage */
  private $storage;

  public function __construct(
    WorkflowStorage $storage
  ) {
    $this->storage = $storage;
  }

  public function createWorkflow(array $data): Workflow {
    // TODO: data & workflow validation (trigger existence, graph consistency, etc.)
    $steps = [];
    foreach ($data['steps'] as $step) {
      $steps[] = new Step(
        $step['id'],
        $step['type'],
        $step['key'],
        $step['next_step_id'] ?? null,
        $step['args'] ?? []
      );
    }
    $workflow = new Workflow($data['name'], $steps);

    $this->storage->createWorkflow($workflow);
    return $workflow;
  }
}
