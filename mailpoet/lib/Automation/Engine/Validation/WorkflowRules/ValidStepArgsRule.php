<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use MailPoet\Validator\ValidationException;
use MailPoet\Validator\Validator;

class ValidStepArgsRule implements WorkflowNodeVisitor {
  /** @var Registry */
  private $registry;

  /** @var Validator */
  private $validator;

  public function __construct(
    Registry $registry,
    Validator $validator
  ) {
    $this->registry = $registry;
    $this->validator = $validator;
  }

  public function initialize(Workflow $workflow): void {
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    $step = $node->getStep();
    $registryStep = $this->registry->getStep($step->getKey());
    if (!$registryStep) {
      return;
    }

    $schema = $registryStep->getArgsSchema();
    $properties = $schema->toArray()['properties'] ?? null;
    if (!$properties) {
      $this->validator->validate($schema, $step->getArgs());
      return;
    }

    $errors = [];
    foreach ($properties as $property => $propertySchema) {
      $schemaToValidate = array_merge(
        $schema->toArray(),
        ['properties' => [$property => $propertySchema]]
      );
      try {
        $this->validator->validateSchemaArray(
          $schemaToValidate,
          $step->getArgs(),
          $property
        );
      } catch (ValidationException $e) {
        $errors[$property] = $e->getWpError()->get_error_code();
      }
    }
    if ($errors) {
      $throwable = ValidationException::create();
      foreach ($errors as $errorKey => $errorMsg) {
        $throwable->withError((string)$errorKey, (string)$errorMsg);
      }
      throw $throwable;
    }
  }

  public function complete(Workflow $workflow): void {
  }
}
