<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\CreateWorkflowFromTemplateController;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Validator\Builder;
use MailPoetVendor\Monolog\DateTimeImmutable;

class WorkflowsCreateFromTemplateEndpoint extends Endpoint {
  /** @var CreateWorkflowFromTemplateController */
  private $createWorkflowFromTemplateController;

  public function __construct(
    CreateWorkflowFromTemplateController $createWorkflowFromTemplateController
  ) {
    $this->createWorkflowFromTemplateController = $createWorkflowFromTemplateController;
  }

  public function handle(Request $request): Response {
    $workflow = $this->createWorkflowFromTemplateController->createWorkflow((string)$request->getParam('slug'));
    return new Response($this->buildWorkflow($workflow));
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
      'steps' => array_map(function (Step $step) {
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'args' => $step->getArgs(),
          'next_steps' => array_map(function (NextStep $nextStep) {
            return $nextStep->toArray();
          }, $step->getNextSteps()),
        ];
      }, $workflow->getSteps()),
    ];
  }

  public static function getRequestSchema(): array {
    return [
      'slug' => Builder::string()->required(),
    ];
  }
}
