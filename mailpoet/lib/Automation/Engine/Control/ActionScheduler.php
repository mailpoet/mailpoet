<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

class ActionScheduler {
  private const GROUP_ID = 'mailpoet-automation';

  public function enqueue(string $hook, array $args = []): int {
    return as_enqueue_async_action($hook, $args, self::GROUP_ID);
  }

  public function schedule(int $timestamp, string $hook, array $args = []): int {
    return as_schedule_single_action($timestamp, $hook, $args, self::GROUP_ID);
  }
}
