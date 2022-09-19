<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Utils\Json;

class Exceptions {
  private const MIGRATION_FAILED = 'mailpoet_automation_migration_failed';
  private const DATABASE_ERROR = 'mailpoet_automation_database_error';
  private const API_METHOD_NOT_ALLOWED = 'mailpoet_automation_api_method_not_allowed';
  private const API_NO_JSON_BODY = 'mailpoet_automation_api_no_json_body';
  private const JSON_NOT_OBJECT = 'mailpoet_automation_json_not_object';
  private const WORKFLOW_NOT_FOUND = 'mailpoet_automation_workflow_not_found';
  private const WORKFLOW_VERSION_NOT_FOUND = 'mailpoet_automation_workflowversion_not_found';
  private const WORKFLOW_RUN_NOT_FOUND = 'mailpoet_automation_workflow_run_not_found';
  private const WORKFLOW_STEP_NOT_FOUND = 'mailpoet_automation_workflow_step_not_found';
  private const WORKFLOW_TRIGGER_NOT_FOUND = 'mailpoet_automation_workflow_trigger_not_found';
  private const WORKFLOW_RUN_NOT_RUNNING = 'mailpoet_automation_workflow_run_not_running';
  private const SUBJECT_NOT_FOUND = 'mailpoet_automation_subject_not_found';
  private const SUBJECT_LOAD_FAILED = 'mailpoet_automation_workflow_subject_load_failed';
  private const SUBJECT_DATA_NOT_FOUND = 'mailpoet_automation_subject_data_not_found';
  private const MULTIPLE_SUBJECTS_FOUND = 'mailpoet_automation_multiple_subjects_found';
  private const WORKFLOW_STRUCTURE_MODIFICATION_NOT_SUPPORTED = 'mailpoet_automation_workflow_structure_modification_not_supported';
  private const WORKFLOW_STRUCTURE_NOT_VALID = 'mailpoet_automation_workflow_structure_not_valid';
  private const WORKFLOW_STEP_MODIFIED_WHEN_UNKNOWN = 'mailpoet_automation_workflow_step_modified_when_unknon';

  public function __construct() {
    throw new InvalidStateException(
      "This is a static factory class. Use it via 'Exception::someError()' factories."
    );
  }

  public static function migrationFailed(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MIGRATION_FAILED)
      // translators: %s is the error message.
      ->withMessage(sprintf(__('Migration failed: %s', 'mailpoet'), $error));
  }

  public static function databaseError(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::DATABASE_ERROR)
      // translators: %s is the error message.
      ->withMessage(sprintf(__('Database error: %s', 'mailpoet'), $error));
  }

  public static function apiMethodNotAllowed(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withStatusCode(405)
      ->withErrorCode(self::API_METHOD_NOT_ALLOWED)
      ->withMessage(__('Method not allowed.', 'mailpoet'));
  }

  public static function apiNoJsonBody(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::API_NO_JSON_BODY)
      ->withMessage(__('No JSON body passed.', 'mailpoet'));
  }

  public static function jsonNotObject(string $json): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::JSON_NOT_OBJECT)
      // translators: %s is the mentioned JSON string.
      ->withMessage(sprintf(__("JSON string '%s' doesn't encode an object.", 'mailpoet'), $json));
  }

  public static function workflowNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_NOT_FOUND)
      // translators: %d is the ID of the workflow.
      ->withMessage(sprintf(__("Workflow with ID '%d' not found.", 'mailpoet'), $id));
  }

  public static function workflowVersionNotFound(int $workflow, int $version): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_VERSION_NOT_FOUND)
      // translators: %1$s is the ID of the workflow, %2$s the version.
      ->withMessage(sprintf(__('Workflow with ID "%1$s" in version "%2$s" not found.', 'mailpoet'), $workflow, $version));
  }

  public static function workflowRunNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_RUN_NOT_FOUND)
      // translators: %d is the ID of the workflow run.
      ->withMessage(sprintf(__("Workflow run with ID '%d' not found.", 'mailpoet'), $id));
  }

  public static function workflowStepNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_STEP_NOT_FOUND)
      // translators: %s is the key of the workflow step.
      ->withMessage(sprintf(__("Workflow step with key '%s' not found.", 'mailpoet'), $key));
  }

  public static function workflowTriggerNotFound(int $workflowId, string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_TRIGGER_NOT_FOUND)
      // translators: %1$s is the key, %2$d is the workflow ID.
      ->withMessage(sprintf(__('Workflow trigger with key "%1$s" not found in workflow ID "%2$d".', 'mailpoet'), $key, $workflowId));
  }

  public static function workflowRunNotRunning(int $id, string $status): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::WORKFLOW_RUN_NOT_RUNNING)
      // translators: %1$d is the ID of the workflow run, %2$s it's current status.
      ->withMessage(sprintf(__('Workflow run with ID "%1$d" is not running. Status: %2$s', 'mailpoet'), $id, $status));
  }

  public static function subjectNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the key of the subject not found.
      ->withMessage(sprintf(__("Subject with key '%s' not found.", 'mailpoet'), $key));
  }

  public static function subjectClassNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the key of the subject class not found.
      ->withMessage(sprintf(__("Subject of class '%s' not found.", 'mailpoet'), $key));
  }

  public static function subjectLoadFailed(string $key, array $args): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::SUBJECT_LOAD_FAILED)
      // translators: %1$s is the name of the key, %2$s the arguments.
      ->withMessage(sprintf(__('Subject with key "%1$s" and args "%2$s" failed to load.', 'mailpoet'), $key, Json::encode($args)));
  }

  public static function subjectDataNotFound(string $key, int $workflowRunId): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_DATA_NOT_FOUND)
      // translators: %1$s is the key of the subject, %2$d is workflow run ID.
      ->withMessage(
        sprintf(__("Subject data for subject with key '%1\$s' not found for workflow run with ID '%2\$d'.", 'mailpoet'), $key, $workflowRunId)
      );
  }

  public static function multipleSubjectsFound(string $key, int $workflowRunId): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MULTIPLE_SUBJECTS_FOUND)
      // translators: %1$s is the key of the subject, %2$d is workflow run ID.
      ->withMessage(
        sprintf(__("Multiple subjects with key '%1\$s' found for workflow run with ID '%2\$d', only one expected.", 'mailpoet'), $key, $workflowRunId)
      );
  }

  public static function workflowStructureModificationNotSupported(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::WORKFLOW_STRUCTURE_MODIFICATION_NOT_SUPPORTED)
      ->withMessage(__("Workflow structure modification not supported.", 'mailpoet'));
  }

  public static function workflowStructureNotValid(string $detail): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::WORKFLOW_STRUCTURE_NOT_VALID)
      // translators: %s is a detailed information
      ->withMessage(sprintf(__("Invalid workflow structure: %s", 'mailpoet'), $detail));
  }

  public static function workflowStepModifiedWhenUnknown(Step $step): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::WORKFLOW_STEP_MODIFIED_WHEN_UNKNOWN)
      // translators: %1$s is the key of the step, %2$s is the type of the step, %3\$s is its ID.
      ->withMessage(
        sprintf(
          __("Modification of step '%1\$s' of type '%2\$s' with ID '%3\$s' is not supported when the related plugin is not active.", 'mailpoet'),
          $step->getKey(),
          $step->getType(),
          $step->getId()
        )
      );
  }
}
