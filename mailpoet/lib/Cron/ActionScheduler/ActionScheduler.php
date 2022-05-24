<?php declare(strict_types = 1);

namespace MailPoet\Cron\ActionScheduler;

class ActionScheduler {
  public const GROUP_ID = 'mailpoet-cron';

  public function scheduleRecurringAction(int $timestamp, int $interval_in_seconds, $hook, $args = []): int {
    return as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args, self::GROUP_ID);
  }

  public function unscheduleAction($hook, $args = []) {
    return as_unschedule_action($hook, $args, self::GROUP_ID);
  }

  public function hasScheduledAction(string $hook, array $args = []): bool {
    return as_has_scheduled_action($hook, $args, self::GROUP_ID);
  }
}
