<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowWalker;
use MailPoet\Automation\Engine\Validation\WorkflowRules\ConsistentStepMapRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\NoCycleRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\NoDuplicateEdgesRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\NoJoinRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\NoSplitRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\NoUnreachableStepsRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\TriggersUnderRootRule;

class WorkflowValidator {
  /** @var WorkflowStepsValidator */
  private $stepsValidator;

  /** @var WorkflowWalker */
  private $workflowWalker;

  public function __construct(
    WorkflowStepsValidator $stepsValidator,
    WorkflowWalker $workflowWalker
  ) {
    $this->workflowWalker = $workflowWalker;
    $this->stepsValidator = $stepsValidator;
  }

  public function validate(Workflow $workflow): void {
    // validate graph
    $this->workflowWalker->walk($workflow, [
      new NoUnreachableStepsRule(),
      new ConsistentStepMapRule(),
      new NoDuplicateEdgesRule(),
      new TriggersUnderRootRule(),
      new NoCycleRule(),
      new NoJoinRule(),
      new NoSplitRule(),
    ]);

    // validate steps
    $this->stepsValidator->validateSteps($workflow);
  }
}
