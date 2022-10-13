<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\DeleteWorkflowController;
use MailPoet\Validator\Builder;

class WorkflowsDeleteEndpoint extends Endpoint {
  /** @var DeleteWorkflowController */
  private $deleteController;

  public function __construct(
    DeleteWorkflowController $deleteController
  ) {
    $this->deleteController = $deleteController;
  }

  public function handle(Request $request): Response {
    $workflowId = intval($request->getParam('id'));
    $this->deleteController->deleteWorkflow($workflowId);
    return new Response(null);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
