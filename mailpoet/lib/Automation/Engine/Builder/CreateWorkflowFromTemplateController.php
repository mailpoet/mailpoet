<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Storage\WorkflowTemplateStorage;
use MailPoet\UnexpectedValueException;

class CreateWorkflowFromTemplateController {
  /** @var WorkflowStorage */
  private $storage;

  /** @var WorkflowTemplateStorage  */
  private $templateStorage;

  public function __construct(
    WorkflowStorage $storage,
    WorkflowTemplateStorage $templateStorage
  ) {
    $this->storage = $storage;
    $this->templateStorage = $templateStorage;
  }

  public function createWorkflow(string $slug): Workflow {

    $template = $this->templateStorage->getTemplateBySlug($slug);
    if (!$template) {
      throw UnexpectedValueException::create()->withMessage('Template not found.');
    }

    $workflowId = $this->storage->createWorkflow($template->getWorkflow());
    $workflow = $this->storage->getWorkflow($workflowId);
    if (!$workflow) {
      throw new InvalidStateException('Workflow not found.');
    }
    return $workflow;
  }
}
