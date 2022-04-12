<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Builder\CreateWorkflowController;

class WorkflowsPostEndpoint extends Endpoint {
  /** @var CreateWorkflowController */
  private $createController;

  public function __construct(
    CreateWorkflowController $createController
  ) {
    $this->createController = $createController;
  }

  public function handle(Request $request): Response {
    // TODO: validation
    $data = $request->getParams();
    $this->createController->createWorkflow($data);
    return new Response();
  }
}
