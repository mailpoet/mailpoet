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
use MailPoet\Automation\Engine\Validation\WorkflowRules\UnknownStepRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\ValidStepArgsRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\ValidStepOrderRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\ValidStepRule;
use MailPoet\Automation\Engine\Validation\WorkflowRules\ValidStepValidationRule;

class WorkflowValidator {
  /** @var WorkflowWalker */
  private $workflowWalker;

  /** @var ValidStepArgsRule */
  private $validStepArgsRule;

  /** @var ValidStepOrderRule */
  private $validStepOrderRule;

  /** @var ValidStepValidationRule */
  private $validStepValidationRule;

  /** @var UnknownStepRule */
  private $unknownStepRule;

  public function __construct(
    UnknownStepRule $unknownStepRule,
    ValidStepArgsRule $validStepArgsRule,
    ValidStepOrderRule $validStepOrderRule,
    ValidStepValidationRule $validStepValidationRule,
    WorkflowWalker $workflowWalker
  ) {
    $this->unknownStepRule = $unknownStepRule;
    $this->validStepArgsRule = $validStepArgsRule;
    $this->validStepOrderRule = $validStepOrderRule;
    $this->validStepValidationRule = $validStepValidationRule;
    $this->workflowWalker = $workflowWalker;
  }

  public function validate(Workflow $workflow): void {
    $this->workflowWalker->walk($workflow, [
      new NoUnreachableStepsRule(),
      new ConsistentStepMapRule(),
      new NoDuplicateEdgesRule(),
      new TriggersUnderRootRule(),
      new NoCycleRule(),
      new NoJoinRule(),
      new NoSplitRule(),
      $this->unknownStepRule,
      new ValidStepRule([
        $this->validStepArgsRule,
        $this->validStepOrderRule,
        $this->validStepValidationRule,
      ]),
    ]);
  }
}
