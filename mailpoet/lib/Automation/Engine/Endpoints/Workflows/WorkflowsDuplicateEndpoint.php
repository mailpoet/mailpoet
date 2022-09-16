<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Builder;

class WorkflowsDuplicateEndpoint extends Endpoint {
  /** @var WorkflowMapper */
  private $workflowMapper;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    WorkflowMapper $workflowMapper,
    WorkflowStorage $workflowStorage
  ) {
    $this->workflowMapper = $workflowMapper;
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
    $duplicateId = $this->workflowStorage->duplicateWorkflow($existingWorkflow);
    $duplicate = $this->workflowStorage->getWorkflow($duplicateId);
    if (!$duplicate instanceof Workflow) {
      throw InvalidStateException::create();
    }
    return new Response($this->workflowMapper->buildWorkflow($duplicate));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
