<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\DuplicateWorkflowController;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Validator\Builder;

class WorkflowsDuplicateEndpoint extends Endpoint {
  /** @var WorkflowMapper */
  private $workflowMapper;

  /** @var DuplicateWorkflowController */
  private $duplicateController;

  public function __construct(
    DuplicateWorkflowController $duplicateController,
    WorkflowMapper $workflowMapper
  ) {
    $this->workflowMapper = $workflowMapper;
    $this->duplicateController = $duplicateController;
  }

  public function handle(Request $request): Response {
    $workflowId = intval($request->getParam('id'));
    $duplicate = $this->duplicateController->duplicateWorkflow($workflowId);
    return new Response($this->workflowMapper->buildWorkflow($duplicate));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
