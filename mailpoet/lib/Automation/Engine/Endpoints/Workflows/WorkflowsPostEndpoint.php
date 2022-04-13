<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Builder\CreateWorkflowController;
use MailPoet\Validator\Builder;

class WorkflowsPostEndpoint extends Endpoint {
  /** @var CreateWorkflowController */
  private $createController;

  public function __construct(
    CreateWorkflowController $createController
  ) {
    $this->createController = $createController;
  }

  public function handle(Request $request): Response {
    $data = $request->getParams();
    $this->createController->createWorkflow($data);
    return new Response();
  }

  public static function getRequestSchema(): array {
    $step = Builder::object([
      'id' => Builder::string()->required(),
      'type' => Builder::string()->required(),
      'key' => Builder::string()->required(),
      'args' => Builder::object(),
      'next_step_id' => Builder::string(),
    ]);

    return [
      'name' => Builder::string()->required(),
      'steps' => Builder::object()->required()->additionalProperties($step),
    ];
  }
}
