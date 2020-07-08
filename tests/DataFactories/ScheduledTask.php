<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTask {
  public function deleteAll() {
    $tasks = \MailPoet\Models\ScheduledTask::findMany();
    foreach ($tasks as $task) {
      $task->delete();
    }
  }

  /**
   * Reschedules tasks created after plugin activation so that they don't block cron tasks in tests
   */
  public function withDefaultTasks() {
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $datetime->addDay();
    $this->scheduleTask(WooCommercePastOrders::TASK_TYPE, $datetime);
    $this->scheduleTask(UnsubscribeTokens::TASK_TYPE, $datetime);
    $this->scheduleTask(SubscriberLinkTokens::TASK_TYPE, $datetime);
    $this->scheduleTask(Beamer::TASK_TYPE, $datetime);
    $this->scheduleTask(InactiveSubscribers::TASK_TYPE, $datetime);
    $this->scheduleTask(Migration::TASK_TYPE, $datetime);
  }

  private function scheduleTask(string $type, Carbon $datetime) {
    $task = \MailPoet\Models\ScheduledTask::where('type', $type)->findOne();
    if (!($task instanceof \MailPoet\Models\ScheduledTask)) {
      $task = \MailPoet\Models\ScheduledTask::create();
    }
    $task->type = $type;
    $task->status = \MailPoet\Models\ScheduledTask::STATUS_SCHEDULED;
    $task->scheduledAt = $datetime;
    $task->save();
  }
}
