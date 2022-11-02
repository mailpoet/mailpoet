<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Util\Security;

class DuplicateWorkflowController {
  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowStorage */
  private $workflowStorage;

  public function __construct(
    WordPress $wordPress,
    WorkflowStorage $workflowStorage
  ) {
    $this->wordPress = $wordPress;
    $this->workflowStorage = $workflowStorage;
  }

  public function duplicateWorkflow(int $id): Workflow {
    $workflow = $this->workflowStorage->getWorkflow($id);
    if (!$workflow) {
      throw Exceptions::workflowNotFound($id);
    }

    $duplicate = new Workflow(
      $this->getName($workflow->getName()),
      $this->getSteps($workflow->getSteps()),
      $this->wordPress->wpGetCurrentUser()
    );
    $duplicate->setStatus(Workflow::STATUS_DRAFT);

    $workflowId = $this->workflowStorage->createWorkflow($duplicate);
    $savedWorkflow = $this->workflowStorage->getWorkflow($workflowId);
    if (!$savedWorkflow) {
      throw new InvalidStateException('Workflow not found.');
    }
    return $savedWorkflow;
  }

  private function getName(string $name): string {
    // translators: %s is the original automation name.
    $newName = sprintf(__('Copy of %s', 'mailpoet'), $name);
    $maxLength = $this->workflowStorage->getNameColumnLength();
    if (strlen($newName) > $maxLength) {
      $append = '…';
      return substr($newName, 0, $maxLength - strlen($append)) . $append;
    }
    return $newName;
  }

  /**
   * @param Step[] $steps
   * @return Step[]
  */
  private function getSteps(array $steps): array {
    $newIds = [];
    foreach ($steps as $step) {
      $id = $step->getId();
      $newIds[$id] = $id === 'root' ? 'root' : $this->getId();
    }

    $newSteps = [];
    foreach ($steps as $step) {
      $newId = $newIds[$step->getId()];
      $newSteps[$newId] = new Step(
        $newId,
        $step->getType(),
        $step->getKey(),
        $step->getArgs(),
        array_map(function (NextStep $nextStep) use ($newIds): NextStep {
          return new NextStep($newIds[$nextStep->getId()]);
        }, $step->getNextSteps())
      );
    }
    return $newSteps;
  }

  private function getId(): string {
    return Security::generateRandomString(16);
  }
}
