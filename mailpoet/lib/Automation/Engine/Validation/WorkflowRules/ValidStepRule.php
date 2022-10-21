<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Validation\WorkflowRules;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNode;
use MailPoet\Automation\Engine\Validation\WorkflowGraph\WorkflowNodeVisitor;
use MailPoet\Validator\ValidationException;
use Throwable;

class ValidStepRule implements WorkflowNodeVisitor {
  /** @var WorkflowNodeVisitor[] */
  private $rules;

  /** @var array<string, array{step_id: string, fields: array<string,string>}> */
  private $errors = [];

  /** @param WorkflowNodeVisitor[] $rules */
  public function __construct(
    array $rules
  ) {
    $this->rules = $rules;
  }

  public function initialize(Workflow $workflow): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $rule->initialize($workflow);
    }
  }

  public function visitNode(Workflow $workflow, WorkflowNode $node): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $stepId = $node->getStep()->getId();
      try {
        $rule->visitNode($workflow, $node);
      } catch (UnexpectedValueException $e) {
        if (!isset($this->errors[$stepId])) {
          $this->errors[$stepId] = ['step_id' => $stepId, 'message' => $e->getMessage(), 'fields' => []];
        }
        $this->errors[$stepId]['fields'] = array_merge(
          $this->mapErrorCodesToErrorMessages($e->getErrors()),
          $this->errors[$stepId]['fields']
        );
      } catch (ValidationException $e) {
        if (!isset($this->errors[$stepId])) {
          $this->errors[$stepId] = ['step_id' => $stepId, 'message' => $e->getMessage(), 'fields' => []];
        }
        $this->errors[$stepId]['fields'] = array_merge(
          $this->mapErrorCodesToErrorMessages($e->getErrors()),
          $this->errors[$stepId]['fields']
        );
      } catch (Throwable $e) {
        if (!isset($this->errors[$stepId])) {
          $this->errors[$stepId] = ['step_id' => $stepId, 'message' => __('Unknown error.', 'mailpoet'), 'fields' => []];
        }
      }
    }
  }

  private function mapErrorCodesToErrorMessages(array $errorCodes): array {

    return array_map(
      function(string $errorCode): string {
        switch ($errorCode) {
          case "rest_property_required":
            return __('This is a required field.', 'mailpoet');
          case "rest_additional_properties_forbidden":
            return "";
          case "rest_too_few_properties":
            return "";
          case "rest_too_many_properties":
            return "";
          case "rest_invalid_type":
            return __('This field is not well formed.', 'mailpoet');
          case "rest_too_few_items":
            return __('Please add more items.', 'mailpoet');
          case "rest_too_many_items":
            return __('Please remove some items.', 'mailpoet');
          case "rest_duplicate_items":
            return __('Please remove duplicate items.', 'mailpoet');
          case "rest_invalid_multiple":
            return __('This field is not well formed.', 'mailpoet');
          case "rest_out_of_bounds":
            return __('This value is out of bounds.', 'mailpoet');
          case "rest_too_short":
            return __('This value is not long enough.', 'mailpoet');
          case "rest_too_long":
            return __('This value is too long.', 'mailpoet');
          case "rest_invalid_pattern":
            return __('This value is not well formed.', 'mailpoet');
          case "rest_no_matching_schema":
            return __('This value does not match the expected format.', 'mailpoet');
          case "rest_one_of_multiple_matches":
            return __('This value is not matching the correct times.', 'mailpoet');
          case "rest_not_in_enum":
            return __('This value is not well formed.', 'mailpoet');
          case "rest_invalid_hex_color":
            return __('This value is not a hex formatted color.', 'mailpoet');
          case "rest_invalid_date":
            return __('This value is not a date.', 'mailpoet');
          case "rest_invalid_email":
            return __('This value is not an email address.', 'mailpoet');
          case "rest_invalid_ip":
            return __('This value is not an IP address.', 'mailpoet');
          case "rest_invalid_uuid":
            return __('This value is not an UUID.', 'mailpoet');
          default:
            return $errorCode;
        }
      },
      $errorCodes
    );
  }

  public function complete(Workflow $workflow): void {
    // run full step validation only for active workflows
    if ($workflow->getStatus() !== Workflow::STATUS_ACTIVE) {
      return;
    }

    foreach ($this->rules as $rule) {
      $rule->complete($workflow);
    }

    if ($this->errors) {
      throw Exceptions::workflowNotValid(__('Some steps are not valid', 'mailpoet'), $this->errors);
    }
  }
}
