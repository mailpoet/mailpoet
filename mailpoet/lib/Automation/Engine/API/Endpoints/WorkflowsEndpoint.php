<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API\Endpoints;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Builder\CreateWorkflowController;

class WorkflowsEndpoint extends Endpoint {
  /** @var CreateWorkflowController */
  private $createController;

  public function __construct(
    CreateWorkflowController $createController
  ) {
    $this->createController = $createController;
  }

  public function get(Request $request): Response {
    return new Response(['message' => 'Hello world.']);
  }

  public function post(Request $request): Response {
    // TODO: validation
    $body = $request->getBody();
    $this->createController->createWorkflow($body);
    return new Response();
  }
}
