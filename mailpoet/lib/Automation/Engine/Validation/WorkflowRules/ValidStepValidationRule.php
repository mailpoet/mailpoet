<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use Throwable;

class ValidStepValidationRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var array<string, array{step_id: string, message: string}> */
  private $errors = [];

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function initialize(Workflow $workflow): void {
    $this->errors = [];
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      return;
    }

    // run custom step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    try {
      if ($registryStep instanceof Action) {
        $subjects = $this->collectSubjects($workflow, $node->getParents());
        $args = new StepValidationArgs($workflow, $step, $subjects);
        $registryStep->validate($args);
      }

    } catch (Throwable $e) {
      $this->errors[$step->getId()] = [
        'step_id' => $step->getId(),
        'message' => $e instanceof ValidationException ? $e->getMessage() : __('Unknown error.', 'mailpoet'),
      ];
    }
  }

  public function complete(Workflow $workflow): void {
    if ($this->errors) {
      throw Exceptions::workflowNotValid(__('Some steps are not valid', 'mailpoet'), $this->errors);
    }
  }

  /**
   * @param Step[] $parents
   * @return Subject<Payload>[]
   */
  private function collectSubjects(Workflow $workflow, array $parents): array {
    $triggers = array_filter($parents, function (Step $step) {
      return $step->getType() === Step::TYPE_TRIGGER;
    });

    $subjectKeys = [];
    foreach ($triggers as $trigger) {
      $registryTrigger = $this->registry->getTrigger($trigger->getKey());
      if (!$registryTrigger) {
        throw Exceptions::workflowTriggerNotFound($workflow->getId(), $trigger->getKey());
      }
      $subjectKeys = array_merge($subjectKeys, $registryTrigger->getSubjectKeys());
    }

    $subjects = [];
    foreach (array_unique($subjectKeys) as $key) {
      $subject = $this->registry->getSubject($key);
      if (!$subject) {
        throw Exceptions::subjectNotFound($key);
      }
      $subjects[] = $subject;
    }
    return $subjects;
  }
}
