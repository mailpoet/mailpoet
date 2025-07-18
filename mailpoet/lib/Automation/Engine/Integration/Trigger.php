<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\StepRunArgs;

/**
 * Trigger Interface
 *
 * Triggers are the entry points of automations that determine when an automation
 * should start executing based on specific events or conditions.
 *
 */
interface Trigger extends Step {
  /**
   * Register WordPress hooks to listen for trigger events.
   *
   * This method is called when automations using this trigger are activated.
   * Implementations should use WordPress action/filter hooks to listen for
   * specific events that should trigger the automation.
   *
   * @return void
   */
  public function registerHooks(): void;

  /**
   * Determine if the trigger should fire based on the current context.
   *
   * This method is called when a trigger event occurs to determine if the
   * automation should actually start. It receives the step run arguments
   * containing automation data, subjects, and other context information.
   *
   * The method should return true if the automation should proceed, false otherwise.
   * This allows for fine-grained control over when automations execute.
   *
   * @param StepRunArgs $args The step run arguments containing automation context
   * @return bool True if the automation should proceed, false otherwise
   */
  public function isTriggeredBy(StepRunArgs $args): bool;
}
