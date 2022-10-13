<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\CreateWorkflowFromTemplateController;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Validator\Builder;

class WorkflowsCreateFromTemplateEndpoint extends Endpoint {
  /** @var CreateWorkflowFromTemplateController */
  private $createWorkflowFromTemplateController;

  /** @var WorkflowMapper */
  private $workflowMapper;

  public function __construct(
    CreateWorkflowFromTemplateController $createWorkflowFromTemplateController,
    WorkflowMapper $workflowMapper
  ) {
    $this->createWorkflowFromTemplateController = $createWorkflowFromTemplateController;
    $this->workflowMapper = $workflowMapper;
  }

  public function handle(Request $request): Response {
    $workflow = $this->createWorkflowFromTemplateController->createWorkflow((string)$request->getParam('slug'));
    return new Response($this->workflowMapper->buildWorkflow($workflow));
  }

  public static function getRequestSchema(): array {
    return [
      'slug' => Builder::string()->required(),
    ];
  }
}
