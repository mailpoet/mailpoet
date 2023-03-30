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
  private const JSON_NOT_OBJECT = 'mailpoet_automation_json_not_object';
  private const AUTOMATION_NOT_FOUND = 'mailpoet_automation_not_found';
  private const AUTOMATION_VERSION_NOT_FOUND = 'mailpoet_automation_version_not_found';
  private const AUTOMATION_NOT_ACTIVE = 'mailpoet_automation_not_active';
  private const AUTOMATION_RUN_NOT_FOUND = 'mailpoet_automation_run_not_found';
  private const AUTOMATION_STEP_NOT_FOUND = 'mailpoet_automation_step_not_found';
  private const AUTOMATION_TRIGGER_NOT_FOUND = 'mailpoet_automation_trigger_not_found';
  private const AUTOMATION_RUN_NOT_RUNNING = 'mailpoet_automation_run_not_running';
  private const SUBJECT_NOT_FOUND = 'mailpoet_automation_subject_not_found';
  private const SUBJECT_LOAD_FAILED = 'mailpoet_automation_subject_load_failed';
  private const SUBJECT_DATA_NOT_FOUND = 'mailpoet_automation_subject_data_not_found';
  private const MULTIPLE_SUBJECTS_FOUND = 'mailpoet_automation_multiple_subjects_found';
  private const PAYLOAD_NOT_FOUND = 'mailpoet_automation_payload_not_found';
  private const MULTIPLE_PAYLOADS_FOUND = 'mailpoet_automation_multiple_payloads_found';
  private const AUTOMATION_STRUCTURE_MODIFICATION_NOT_SUPPORTED = 'mailpoet_automation_structure_modification_not_supported';
  private const AUTOMATION_STRUCTURE_NOT_VALID = 'mailpoet_automation_structure_not_valid';
  private const AUTOMATION_STEP_MODIFIED_WHEN_UNKNOWN = 'mailpoet_automation_step_modified_when_unknown';
  private const AUTOMATION_NOT_VALID = 'mailpoet_automation_not_valid';
  private const MISSING_REQUIRED_SUBJECTS = 'mailpoet_automation_missing_required_subjects';
  private const AUTOMATION_NOT_TRASHED = 'mailpoet_automation_not_trashed';
  private const AUTOMATION_TEMPLATE_NOT_FOUND = 'mailpoet_automation_template_not_found';
  private const AUTOMATION_HAS_ACTIVE_RUNS = 'mailpoet_automation_has_active_runs';

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

  public static function jsonNotObject(string $json): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::JSON_NOT_OBJECT)
      // translators: %s is the mentioned JSON string.
      ->withMessage(sprintf(__("JSON string '%s' doesn't encode an object.", 'mailpoet'), $json));
  }

  public static function automationNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_NOT_FOUND)
      // translators: %d is the ID of the automation.
      ->withMessage(sprintf(__("Automation with ID '%d' not found.", 'mailpoet'), $id));
  }

  public static function automationVersionNotFound(int $automation, int $version): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_VERSION_NOT_FOUND)
      // translators: %1$s is the ID of the automation, %2$s the version.
      ->withMessage(sprintf(__('Automation with ID "%1$s" in version "%2$s" not found.', 'mailpoet'), $automation, $version));
  }

  public static function automationNotActive(int $automation): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::AUTOMATION_NOT_ACTIVE)
      // translators: %1$s is the ID of the automation.
      ->withMessage(sprintf(__('Automation with ID "%1$s" in no longer active.', 'mailpoet'), $automation));
  }

  public static function automationRunNotFound(int $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_RUN_NOT_FOUND)
      // translators: %d is the ID of the automation run.
      ->withMessage(sprintf(__("Automation run with ID '%d' not found.", 'mailpoet'), $id));
  }

  public static function automationStepNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_STEP_NOT_FOUND)
      // translators: %s is the key of the automation step.
      ->withMessage(sprintf(__("Automation step with key '%s' not found.", 'mailpoet'), $key));
  }

  public static function automationTriggerNotFound(int $automationId, string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_TRIGGER_NOT_FOUND)
      // translators: %1$s is the key, %2$d is the automation ID.
      ->withMessage(sprintf(__('Automation trigger with key "%1$s" not found in automation ID "%2$d".', 'mailpoet'), $key, $automationId));
  }

  public static function automationRunNotRunning(int $id, string $status): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::AUTOMATION_RUN_NOT_RUNNING)
      // translators: %1$d is the ID of the automation run, %2$s its current status.
      ->withMessage(sprintf(__('Automation run with ID "%1$d" is not running. Status: %2$s', 'mailpoet'), $id, $status));
  }

  public static function subjectNotFound(string $key): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the key of the subject not found.
      ->withMessage(sprintf(__("Subject with key '%s' not found.", 'mailpoet'), $key));
  }

  public static function subjectClassNotFound(string $class): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_NOT_FOUND)
      // translators: %s is the class name of the subject not found.
      ->withMessage(sprintf(__("Subject of class '%s' not found.", 'mailpoet'), $class));
  }

  public static function subjectLoadFailed(string $key, array $args): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::SUBJECT_LOAD_FAILED)
      // translators: %1$s is the name of the key, %2$s the arguments.
      ->withMessage(sprintf(__('Subject with key "%1$s" and args "%2$s" failed to load.', 'mailpoet'), $key, Json::encode($args)));
  }

  public static function subjectDataNotFound(string $key, int $automationRunId): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::SUBJECT_DATA_NOT_FOUND)
      // translators: %1$s is the key of the subject, %2$d is automation run ID.
      ->withMessage(
        sprintf(__("Subject data for subject with key '%1\$s' not found for automation run with ID '%2\$d'.", 'mailpoet'), $key, $automationRunId)
      );
  }

  public static function multipleSubjectsFound(string $key, int $automationRunId): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::MULTIPLE_SUBJECTS_FOUND)
      // translators: %1$s is the key of the subject, %2$d is automation run ID.
      ->withMessage(
        sprintf(__("Multiple subjects with key '%1\$s' found for automation run with ID '%2\$d', only one expected.", 'mailpoet'), $key, $automationRunId)
      );
  }

  public static function payloadNotFound(string $class, int $automationRunId): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::PAYLOAD_NOT_FOUND)
      // translators: %1$s is the class of the payload, %2$d is automation run ID.
      ->withMessage(
        sprintf(__("Payload of class '%1\$s' not found for automation run with ID '%2\$d'.", 'mailpoet'), $class, $automationRunId)
      );
  }

  public static function multiplePayloadsFound(string $class, int $automationRunId): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::MULTIPLE_PAYLOADS_FOUND)
      // translators: %1$s is the class of the payloads, %2$d is automation run ID.
      ->withMessage(
        sprintf(__("Multiple payloads of class '%1\$s' found for automation run with ID '%2\$d'.", 'mailpoet'), $class, $automationRunId)
      );
  }

  public static function automationStructureModificationNotSupported(): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::AUTOMATION_STRUCTURE_MODIFICATION_NOT_SUPPORTED)
      ->withMessage(__('Automation structure modification not supported.', 'mailpoet'));
  }

  public static function automationStructureNotValid(string $detail, string $ruleId): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::AUTOMATION_STRUCTURE_NOT_VALID)
      // translators: %s is a detailed information
      ->withMessage(sprintf(__("Invalid automation structure: %s", 'mailpoet'), $detail))
      ->withErrors(['rule_id' => $ruleId]);
  }

  public static function automationStepModifiedWhenUnknown(Step $step): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::AUTOMATION_STEP_MODIFIED_WHEN_UNKNOWN)
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

  public static function automationNotValid(string $detail, array $errors): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::AUTOMATION_NOT_VALID)
      // translators: %s is a detailed information
      ->withMessage(sprintf(__("Automation validation failed: %s", 'mailpoet'), $detail))
      ->withErrors($errors);
  }

  public static function missingRequiredSubjects(Step $step, array $missingSubjectKeys): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::MISSING_REQUIRED_SUBJECTS)
      // translators: %1$s is the key of the step, %2$s are the missing subject keys.
      ->withMessage(
        sprintf(
          __("Step with ID '%1\$s' is missing required subjects with keys: %2\$s", 'mailpoet'),
          $step->getId(),
          implode(', ', $missingSubjectKeys)
        )
      );
  }

  public static function automationNotTrashed(int $id): UnexpectedValueException {
    return UnexpectedValueException::create()
      ->withErrorCode(self::AUTOMATION_NOT_TRASHED)
      // translators: %d is the ID of the automation.
      ->withMessage(sprintf(__("Can't delete automation with ID '%d' because it was not trashed.", 'mailpoet'), $id));
  }

  public static function automationTemplateNotFound(string $id): NotFoundException {
    return NotFoundException::create()
      ->withErrorCode(self::AUTOMATION_TEMPLATE_NOT_FOUND)
      // translators: %d is the ID of the automation template.
      ->withMessage(sprintf(__("Automation template with ID '%d' not found.", 'mailpoet'), $id));
  }

  /**
   * This is a temporary block, see MAILPOET-4744
   */
  public static function automationHasActiveRuns(int $id): InvalidStateException {
    return InvalidStateException::create()
      ->withErrorCode(self::AUTOMATION_HAS_ACTIVE_RUNS)
      // translators: %d is the ID of the automation.
      ->withMessage(sprintf(__("Can not update automation with ID '%d' because users are currently active.", 'mailpoet'), $id));
  }
}
