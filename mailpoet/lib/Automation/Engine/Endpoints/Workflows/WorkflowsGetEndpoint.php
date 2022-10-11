<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use DateTimeImmutable;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowStatistics;
use MailPoet\Automation\Engine\Storage\WorkflowStatisticsStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Validator\Builder;

class WorkflowsGetEndpoint extends Endpoint {
  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowStatisticsStorage  */
  private $statisticsStorage;

  public function __construct(
    WorkflowStorage $workflowStorage,
    WorkflowStatisticsStorage $statisticsStorage
  ) {
    $this->workflowStorage = $workflowStorage;
    $this->statisticsStorage = $statisticsStorage;
  }

  public function handle(Request $request): Response {
    $status = $request->getParam('status') ? (array)$request->getParam('status') : null;
    $workflows = $this->workflowStorage->getWorkflows($status);
    $statistics = $this->statisticsStorage->getWorkflowStatisticsForWorkflows(...$workflows);
    return new Response(array_map(function (Workflow $workflow) use ($statistics) {
      return $this->buildWorkflow($workflow, $statistics[$workflow->getId()]);
    }, $workflows));
  }

  public static function getRequestSchema(): array {
    return [
      'status' => Builder::array(Builder::string()),
    ];
  }

  private function buildWorkflow(Workflow $workflow, WorkflowStatistics $statistics): array {
    return [
      'id' => $workflow->getId(),
      'name' => $workflow->getName(),
      'status' => $workflow->getStatus(),
      'created_at' => $workflow->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $workflow->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'stats' => $statistics->toArray(),
      'activated_at' => $workflow->getActivatedAt() ? $workflow->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $workflow->getAuthor()->ID,
        'name' => $workflow->getAuthor()->display_name,
      ],
    ];
  }
}
