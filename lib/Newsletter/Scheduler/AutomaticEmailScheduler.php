<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;

class AutomaticEmailScheduler {

  function scheduleAutomaticEmail($group, $event, $scheduling_condition = false, $subscriber_id = false, $meta = false) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) continue;
      if (is_callable($scheduling_condition) && !$scheduling_condition($newsletter)) continue;
      $this->createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta);
    }
  }

  function scheduleOrRescheduleAutomaticEmail($group, $event, $subscriber_id, $meta = false) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task);
      } else {
        $this->createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta);
      }
    }
  }

  function rescheduleAutomaticEmail($group, $event, $subscriber_id) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task);
      }
    }
  }

  function cancelAutomaticEmail($group, $event, $subscriber_id) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return false;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->id, $subscriber_id);
      if ($task) {
        SendingQueue::where('task_id', $task->id)->deleteMany();
        ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
        $task->delete();
      }
    }
  }

  function createAutomaticEmailSendingTask($newsletter, $subscriber_id, $meta) {
    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $newsletter->id;
    if ($newsletter->sendTo === 'user' && $subscriber_id) {
      $sending_task->setSubscribers([$subscriber_id]);
    }
    if ($meta) {
      $sending_task->__set('meta', $meta);
    }
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->priority = SendingQueue::PRIORITY_MEDIUM;

    $sending_task->scheduled_at = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    return $sending_task->save();
  }

  private function rescheduleAutomaticEmailSendingTask($newsletter, $task) {
    // compute new 'scheduled_at' from now
    $task->scheduled_at = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber);
    $task->save();
  }

}
