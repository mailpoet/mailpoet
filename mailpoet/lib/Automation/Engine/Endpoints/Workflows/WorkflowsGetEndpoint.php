<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use DateTimeImmutable;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Builder;

class WorkflowsGetEndpoint extends Endpoint {
  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    WorkflowStorage $workflowStorage
  ) {
    $this->workflowStorage = $workflowStorage;
  }

  public function handle(Request $request): Response {
    $status = $request->getParam('status') ? (array)$request->getParam('status') : null;
    $workflows = $this->workflowStorage->getWorkflows($status);
    return new Response(array_map(function (Workflow $workflow) {
      return $this->buildWorkflow($workflow);
    }, $workflows));
  }

  public static function getRequestSchema(): array {
    return [
      'status' => Builder::array(Builder::string()),
    ];
  }

  private function buildWorkflow(Workflow $workflow): array {
    return [
      'id' => $workflow->getId(),
      'name' => $workflow->getName(),
      'status' => $workflow->getStatus(),
      'created_at' => $workflow->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $workflow->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'activated_at' => $workflow->getActivatedAt() ? $workflow->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $workflow->getAuthor()->ID,
        'name' => $workflow->getAuthor()->display_name,
      ],
    ];
  }
}
