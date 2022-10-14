<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Builder;

class WorkflowsGetEndpoint extends Endpoint {
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
    $status = $request->getParam('status') ? (array)$request->getParam('status') : null;
    $workflows = $this->workflowStorage->getWorkflows($status);
    return new Response($this->workflowMapper->buildWorkflowList($workflows));
  }

  public static function getRequestSchema(): array {
    return [
      'status' => Builder::array(Builder::string()),
    ];
  }
}
