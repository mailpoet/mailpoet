<?php declare(strict_types = 1);

namespace MailPoet\Cron\ActionScheduler;

class ActionSchedulerTestHelper {
  public function getMailPoetScheduledActions(): array {
    $actions = as_get_scheduled_actions([
      'group' => ActionScheduler::GROUP_ID,
      'status' => [\ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING],
    ]);
    return $actions;
  }

  public function getMailPoetCompleteActions(): array {
    $actions = as_get_scheduled_actions([
      'group' => ActionScheduler::GROUP_ID,
      'status' => [\ActionScheduler_Store::STATUS_COMPLETE],
    ]);
    return $actions;
  }

  public function getMailPoetCronActions(): array {
    $actions = as_get_scheduled_actions([
      'group' => ActionScheduler::GROUP_ID,
    ]);
    return $actions;
  }
}
