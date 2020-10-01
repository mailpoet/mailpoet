<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;

class AutomaticEmailScheduler {
  public function scheduleAutomaticEmail($group, $event, $schedulingCondition = false, $subscriberId = false, $meta = false) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) continue;
      if (is_callable($schedulingCondition) && !$schedulingCondition($newsletter)) continue;
      $this->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
    }
  }

  public function scheduleOrRescheduleAutomaticEmail($group, $event, $subscriberId, $meta = false) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task, $meta);
      } else {
        $this->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
      }
    }
  }

  public function rescheduleAutomaticEmail($group, $event, $subscriberId) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task);
      }
    }
  }

  public function cancelAutomaticEmail($group, $event, $subscriberId) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriberId);
      if ($task) {
        SendingQueue::where('task_id', $task->id)->deleteMany();
        ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
        $task->delete();
      }
    }
  }

  public function createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta = false) {
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->id;
    if ($newsletter->sendTo === 'user' && $subscriberId) {
      $sendingTask->setSubscribers([$subscriberId]);
    }
    if ($meta) {
      $sendingTask->__set('meta', $meta);
    }
    $sendingTask->status = SendingQueue::STATUS_SCHEDULED;
    $sendingTask->priority = SendingQueue::PRIORITY_MEDIUM;

    $sendingTask->scheduledAt = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    return $sendingTask->save();
  }

  private function rescheduleAutomaticEmailSendingTask($newsletter, $task, $meta = false) {
    if ($meta) {
      $task->__set('meta', $meta);
    }
    // compute new 'scheduled_at' from now
    $task->scheduledAt = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    $task->save();
  }
}
