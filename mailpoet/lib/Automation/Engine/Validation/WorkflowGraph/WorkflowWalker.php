<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowGraph;

use Generator;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;

class WorkflowWalker {
  /** @param WorkflowNodeVisitor[] $visitors */
  public function walk(Workflow $workflow, array $visitors = []): void {
    $steps = $workflow->getSteps();
    $root = $steps['root'] ?? null;
    if (!$root) {
      throw Exceptions::workflowStructureNotValid(__("Workflow must contain a 'root' step", 'mailpoet'));
    }

    foreach ($visitors as $visitor) {
      $visitor->initialize($workflow);
    }

    foreach ($this->walkStepsDepthFirstPreOrder($steps, $root) as $record) {
      [$step, $parents] = $record;
      foreach ($visitors as $visitor) {
        $visitor->visitNode($workflow, new WorkflowNode($step, array_values($parents)));
      }
    }

    foreach ($visitors as $visitor) {
      $visitor->complete($workflow);
    }
  }

  /**
   * @param array<string, Step> $steps
   * @return Generator<array{0: Step, 1: array<string, Step>}>
   */
  private function walkStepsDepthFirstPreOrder(array $steps, Step $root): Generator {
    /** @var array{0: Step, 1: array<string, Step>}[] $stack */
    $stack = [
      [$root, []],
    ];

    do {
      $record = array_pop($stack);
      if (!$record) {
        throw new InvalidStateException();
      }
      yield $record;
      [$step, $parents] = $record;

      foreach (array_reverse($step->getNextSteps()) as $nextStepData) {
        $nextStepId = $nextStepData->getId();
        $nextStep = $steps[$nextStepId] ?? null;
        if (!$nextStep) {
          throw $this->createStepNotFoundException($nextStepId, $step->getId());
        }

        $nextStepParents = array_merge($parents, [$step->getId() => $step]);
        if (isset($nextStepParents[$nextStepId])) {
          continue; // cycle detected, do not enter the path again
        }
        array_push($stack, [$nextStep, $nextStepParents]);
      }
    } while (count($stack) > 0);
  }

  private function createStepNotFoundException(string $stepId, string $parentStepId): UnexpectedValueException {
    return Exceptions::workflowStructureNotValid(
      // translators: %1$s is ID of the step not found, %2$s is ID of the step that references it
      sprintf(
        __("Step with ID '%1\$s' not found (referenced from '%2\$s')", 'mailpoet'),
        $stepId,
        $parentStepId
      )
    );
  }
}
