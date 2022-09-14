<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Storage\WorkflowTemplateStorage;
use MailPoet\Automation\Engine\Validation\WorkflowValidator;
use MailPoet\UnexpectedValueException;

class CreateWorkflowFromTemplateController {
  /** @var WorkflowStorage */
  private $storage;

  /** @var WorkflowTemplateStorage  */
  private $templateStorage;

  /** @var WorkflowValidator */
  private $workflowValidator;

  public function __construct(
    WorkflowStorage $storage,
    WorkflowTemplateStorage $templateStorage,
    WorkflowValidator $workflowValidator
  ) {
    $this->storage = $storage;
    $this->templateStorage = $templateStorage;
    $this->workflowValidator = $workflowValidator;
  }

  public function createWorkflow(string $slug): Workflow {
    $template = $this->templateStorage->getTemplateBySlug($slug);
    if (!$template) {
      throw UnexpectedValueException::create()->withMessage('Template not found.');
    }

    $workflow = $template->getWorkflow();
    $this->workflowValidator->validate($workflow);
    $workflowId = $this->storage->createWorkflow($workflow);
    $savedWorkflow = $this->storage->getWorkflow($workflowId);
    if (!$savedWorkflow) {
      throw new InvalidStateException('Workflow not found.');
    }
    return $savedWorkflow;
  }
}
