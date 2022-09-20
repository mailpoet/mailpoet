<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Builder;

class WorkflowsDeleteEndpoint extends Endpoint {
  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    WorkflowStorage $workflowStorage
  ) {
    $this->workflowStorage = $workflowStorage;
  }

  public function handle(Request $request): Response {
    $workflowId = $request->getParam('id');
    if (!is_int($workflowId)) {
      throw InvalidStateException::create();
    }
    $existingWorkflow = $this->workflowStorage->getWorkflow($workflowId);
    if (!$existingWorkflow instanceof Workflow) {
      throw InvalidStateException::create();
    }
    $this->workflowStorage->deleteWorkflow($existingWorkflow);

    return new Response(null);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
