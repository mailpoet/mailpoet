<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\UpdateWorkflowController;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Automation\Engine\Validation\WorkflowSchema;
use MailPoet\Validator\Builder;

class WorkflowsPutEndpoint extends Endpoint {
  /** @var UpdateWorkflowController */
  private $updateController;

  /** @var WorkflowMapper */
  private $workflowMapper;

  public function __construct(
    UpdateWorkflowController $updateController,
    WorkflowMapper $workflowMapper
  ) {
    $this->updateController = $updateController;
    $this->workflowMapper = $workflowMapper;
  }

  public function handle(Request $request): Response {
    $data = $request->getParams();
    $workflow = $this->updateController->updateWorkflow(intval($request->getParam('id')), $data);
    return new Response($this->workflowMapper->buildWorkflow($workflow));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->minLength(1),
      'status' => Builder::string(),
      'steps' => WorkflowSchema::getStepsSchema(),
    ];
  }
}
