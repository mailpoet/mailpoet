<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Builder\CreateWorkflowFromTemplateController;
use MailPoet\Validator\Builder;

class WorkflowsCreateFromTemplateEndpoint extends Endpoint {
  /** @var CreateWorkflowFromTemplateController */
  private $createWorkflowFromTemplateController;

  public function __construct(
    CreateWorkflowFromTemplateController $createWorkflowFromTemplateController
  ) {
    $this->createWorkflowFromTemplateController = $createWorkflowFromTemplateController;
  }

  public function handle(Request $request): Response {
    $data = $request->getParams();
    $this->createWorkflowFromTemplateController->createWorkflow($data);
    return new Response();
  }

  public static function getRequestSchema(): array {
    return [
      'name' => Builder::string()->required(),
      'template' => Builder::string()->required(),
    ];
  }
}
