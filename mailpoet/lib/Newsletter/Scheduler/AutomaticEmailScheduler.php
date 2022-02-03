<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;

class AutomaticEmailScheduler {
  /** @var WPFunctions|null */
  private $wp;

  public function __construct(
    ?WPFunctions $wp = null
  ) {
    $this->wp = $wp;
  }

  public function scheduleAutomaticEmail($group, $event, $schedulingCondition = false, $subscriberId = false, $meta = false, $metaModifier = null) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event !== $event) continue;
      if (is_callable($schedulingCondition) && !$schedulingCondition($newsletter)) continue;

      /**
       * $meta will be the same for all newsletters by default. If we need to store newsletter-specific meta, the
       * $metaModifier callback can be used.
       *
       * This was introduced because of WooCommerce product purchase automatic emails. We only want to store the
       * product IDs that specifically triggered a newsletter, but $meta includes ALL the product IDs
       * or category IDs from an order.
       */
      if (is_callable($metaModifier)) {
        $meta = $metaModifier($newsletter, $meta);
      }
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

    $sendingTask->scheduledAt = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber, $this->wp);
    return $sendingTask->save();
  }

  private function rescheduleAutomaticEmailSendingTask($newsletter, ScheduledTask $task, $meta = false) {
    $sendingTask = SendingTask::createFromScheduledTask($task);
    if ($meta) {
      $sendingTask->__set('meta', $meta);
    }
    // compute new 'scheduled_at' from now
    $sendingTask->scheduledAt = Scheduler::getScheduledTimeWithDelay($newsletter->afterTimeType, $newsletter->afterTimeNumber, $this->wp);
    $sendingTask->save();
  }
}
