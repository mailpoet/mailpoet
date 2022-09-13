<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use DateTimeImmutable;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\UpdateWorkflowController;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Validation\WorkflowSchema;
use MailPoet\Validator\Builder;

class WorkflowsPutEndpoint extends Endpoint {
  /** @var UpdateWorkflowController */
  private $updateController;

  public function __construct(
    UpdateWorkflowController $updateController
  ) {
    $this->updateController = $updateController;
  }

  public function handle(Request $request): Response {
    $data = $request->getParams();
    $workflow = $this->updateController->updateWorkflow(intval($request->getParam('id')), $data);
    return new Response($this->buildWorkflow($workflow));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->minLength(1),
      'status' => Builder::string(),
      'steps' => WorkflowSchema::getStepsSchema(),
    ];
  }

  protected function buildWorkflow(Workflow $workflow): array {
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
}
