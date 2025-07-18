<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * Step Interface
 *
 * Defines the base contract for all automation steps in the MailPoet automation system.
 * Can be either triggers, actions, or other step types.
 */
interface Step {
  /**
   * Get the unique identifier for this step type.
   *
   * This key is used to identify the step in the automation system and must be
   * unique across all registered steps. It's typically in the format 'vendor:step-name'.
   *
   * @return string The unique step key
   */
  public function getKey(): string;

  /**
   * Get the human-readable name for this step.
   *
   * This name is displayed in the automation editor and should be translatable.
   *
   * @return string The step name
   */
  public function getName(): string;

  /**
   * Get the JSON schema for step configuration arguments.
   *
   * This schema defines the structure and validation rules for the step's
   * configuration. It's used to validate step arguments during automation
   * creation and to generate the UI for step configuration.
   *
   * @return ObjectSchema The JSON schema for step arguments
   */
  public function getArgsSchema(): ObjectSchema;

  /**
   * Get the subject keys that this step requires.
   *
   * Subjects represent data that flows through the automation. Each step
   * declares which subjects it needs to function properly. The automation
   * system ensures these subjects are available before the step executes.
   *
   * @return string[] Array of subject keys required by this step
   */
  public function getSubjectKeys(): array;

  /**
   * Validate the step configuration and context.
   *
   * This method is called during automation validation to ensure the step
   * is properly configured and can execute successfully. It receives the
   * automation context, step data, and available subjects for validation.
   *
   * Implementations should throw ValidationException if validation fails.
   *
   * @param StepValidationArgs $args Validation context containing automation, step, and subjects
   * @return void
   * @throws \MailPoet\Automation\Engine\Integration\ValidationException When validation fails
   */
  public function validate(StepValidationArgs $args): void;
}
