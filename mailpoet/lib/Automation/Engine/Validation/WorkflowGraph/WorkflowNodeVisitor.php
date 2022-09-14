<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowGraph;

use MailPoet\Automation\Engine\Data\Workflow;

interface WorkflowNodeVisitor {
  public function initialize(Workflow $workflow): void;

  public function visitNode(Workflow $workflow, WorkflowNode $node): void;

  public function complete(Workflow $workflow): void;
}
