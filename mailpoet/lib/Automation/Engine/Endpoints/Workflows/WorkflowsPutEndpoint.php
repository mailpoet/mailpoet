<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use DateTimeImmutable;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;
use MailPoet\Automation\Engine\Builder\UpdateWorkflowController;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Validator\Builder;
use stdClass;

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
    $step = Builder::object([
      'id' => Builder::string()->required(),
      'type' => Builder::string()->required(),
      'key' => Builder::string()->required(),
      'args' => Builder::object(),
      'next_step_id' => Builder::string()->nullable(),
    ]);

    return [
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->minLength(1),
      'status' => Builder::string(),
      'steps' => Builder::object()->additionalProperties($step),
    ];
  }

  private function buildWorkflow(Workflow $workflow): array {
    return [
      'id' => $workflow->getId(),
      'name' => $workflow->getName(),
      'status' => $workflow->getStatus(),
      'created_at' => $workflow->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $workflow->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'steps' => array_map(function (Step $step) {
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'next_step_id' => $step->getNextStepId(),
          'args' => $step->getArgs() ?: new stdClass(),
        ];
      }, $workflow->getSteps()),
    ];
  }
}
