<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use ActionScheduler_Action;
use ActionScheduler_Store;

class ActionScheduler {
  private const GROUP_ID = 'mailpoet-automation';

  public function enqueue(string $hook, array $args = []): int {
    $result = as_enqueue_async_action($hook, $args, self::GROUP_ID);
    return is_int($result) ? $result : 0;
  }

  public function schedule(int $timestamp, string $hook, array $args = []): int {
    $result = as_schedule_single_action($timestamp, $hook, $args, self::GROUP_ID);
    return is_int($result) ? $result : 0;
  }

  public function hasScheduledAction(string $hook, array $args = []): bool {
    return as_has_scheduled_action($hook, $args, self::GROUP_ID);
  }

  /**
   * Unlike hasScheduledAction(), this only matches PENDING actions and ignores
   * RUNNING ones. A recurring hook that reschedules itself from within its own
   * handler must use this: while the handler runs, its own action has status
   * RUNNING, so as_has_scheduled_action() would report the action as still
   * scheduled and the next run would never be queued.
   */
  public function hasPendingScheduledAction(string $hook, array $args = []): bool {
    $actions = as_get_scheduled_actions([
      'hook' => $hook,
      'args' => $args,
      'status' => ActionScheduler_Store::STATUS_PENDING,
      'group' => self::GROUP_ID,
      'per_page' => 1,
    ], 'ids');
    return is_array($actions) && count($actions) > 0;
  }

  /** @return ActionScheduler_Action[] */
  public function getScheduledActions(array $args = []): array {
    return as_get_scheduled_actions(array_merge($args, ['group' => self::GROUP_ID]));
  }

  public function unscheduleAction(string $hook, array $args = []): ?int {
    return as_unschedule_action($hook, $args, self::GROUP_ID);
  }
}
