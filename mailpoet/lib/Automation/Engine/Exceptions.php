<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine;

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
  private const WORKFLOW_RUN_NOT_FOUND = 'mailpoet_automation_workflow_run_not_found';
  private const WORKFLOW_STEP_NOT_FOUND = 'mailpoet_automation_workflow_step_not_found';
  private const WORKFLOW_TRIGGER_NOT_FOUND = 'mailpoet_automation_workflow_trigger_not_found';
  private const WORKFLOW_RUN_NOT_RUNNING = 'mailpoet_automation_workflow_run_not_running';
  private const SUBJECT_NOT_FOUND = 'mailpoet_automation_subject_not_found';
  private const SUBJECT_LOAD_FAILED = 'mailpoet_automation_workflow_subject_load_failed';
  private const MULTIPLE_SUBJECTS_FOUND = 'mailpoet_automation_multiple_subjects_found';
  private const WORKFLOW_STRUCTURE_MODIFICATION_NOT_SUPPORTED = 'mailpoet_automation_workflow_structure_modification_not_supported';

  public function __construct() {
    throw new InvalidStateException(
      "This is a static factory class. Use it via 'Exception::someError()' factories."
    );
  }

  public static function migrationFailed(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MIGRATION_FAILED)
      // translators: %s is the error message.
      ->withMessage(__(sprintf('Migration failed: %s', $error), 'mailpoet'));
  }

  public static function databaseError(string $error): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::DATABASE_ERROR)
      // translators: %s is the error message.
      ->withMessage(__(sprintf('Database error: %s', $error), 'mailpoet'));
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
      ->withMessage(__(sprintf("JSON string '%s' doesn't encode an object.", $json), 'mailpoet'));
  }

  public static function workflowNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_NOT_FOUND)
      // translators: %d is the ID of the workflow.
      ->withMessage(__(sprintf("Workflow with ID '%d' not found.", $id), 'mailpoet'));
  }

  public static function workflowRunNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_RUN_NOT_FOUND)
      // translators: %d is the ID of the workflow run.
      ->withMessage(__(sprintf("Workflow run with ID '%d' not found.", $id), 'mailpoet'));
  }

  public static function workflowStepNotFound(string $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_STEP_NOT_FOUND)
      // translators: %s is the ID of the workflow step.
      ->withMessage(__(sprintf("Workflow step with ID '%s' not found.", $id), 'mailpoet'));
  }

  public static function workflowTriggerNotFound(int $workflowId, string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::WORKFLOW_TRIGGER_NOT_FOUND)
      // translators: %1$s is the key, %2$d is the workflow ID.
      ->withMessage(__(sprintf('Workflow trigger with key "%1$s" not found in workflow ID "%2$d".', $key, $workflowId), 'mailpoet'));
  }

  public static function workflowRunNotRunning(int $id, string $status): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::WORKFLOW_RUN_NOT_RUNNING)
      // translators: %1$d is the ID of the workflow run, %2$s it's current status.
      ->withMessage(__(sprintf('Workflow run with ID "%1$d" is not running. Status: %2$s', $id, $status), 'mailpoet'));
  }

  public static function subjectNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the key of the subject not found.
      ->withMessage(__(sprintf("Subject with key '%s' not found.", $key), 'mailpoet'));
  }

  public static function subjectClassNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the key of the subject class not found.
      ->withMessage(__(sprintf("Subject of class '%s' not found.", $key), 'mailpoet'));
  }

  public static function subjectLoadFailed(string $key, array $args): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::SUBJECT_LOAD_FAILED)
      // translators: %1$s is the name of the key, %2$s the arguments.
      ->withMessage(__(sprintf('Subject with key "%1$s" and args "%2$s" failed to load.', $key, Json::encode($args)), 'mailpoet'));
  }

  public static function multipleSubjectsFound(string $key): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MULTIPLE_SUBJECTS_FOUND)
      // translators: %s is the name of the key.
      ->withMessage(__(sprintf("Multiple subjects with key '%s' found, only one expected.", $key), 'mailpoet'));
  }

  public static function workflowStructureModificationNotSupported(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::WORKFLOW_STRUCTURE_MODIFICATION_NOT_SUPPORTED)
      ->withMessage(__("Workflow structure modification not supported.", 'mailpoet'));
  }
}
