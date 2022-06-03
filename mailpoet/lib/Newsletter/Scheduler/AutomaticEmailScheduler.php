<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;

class AutomaticEmailScheduler {

  /** @var Scheduler */
  private $scheduler;

  public function __construct(
    Scheduler $scheduler
  ) {
    $this->scheduler = $scheduler;
  }

  public function scheduleAutomaticEmail(string $group, string $event, $schedulingCondition = false, $subscriberId = false, $meta = false, $metaModifier = null) {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) continue;
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

  public function scheduleOrRescheduleAutomaticEmail(string $group, string $event, int $subscriberId, array $meta): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->getId(), $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task, $meta);
      } else {
        $this->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
      }
    }
  }

  public function rescheduleAutomaticEmail(string $group, string $event, int $subscriberId): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->getId(), $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task);
      }
    }
  }

  public function cancelAutomaticEmail(string $group, string $event, int $subscriberId): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = ScheduledTask::findOneScheduledByNewsletterIdAndSubscriberId($newsletter->getId(), $subscriberId);
      if ($task) {
        SendingQueue::where('task_id', $task->id)->deleteMany();
        ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
        $task->delete();
      }
    }
  }

  public function createAutomaticEmailSendingTask(NewsletterEntity $newsletter, $subscriberId, $meta = false) {
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->getId();
    if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SEND_TO) === 'user' && $subscriberId) {
      $sendingTask->setSubscribers([$subscriberId]);
    }
    if ($meta) {
      $sendingTask->__set('meta', $meta);
    }
    $sendingTask->status = SendingQueueEntity::STATUS_SCHEDULED;
    $sendingTask->priority = SendingQueueEntity::PRIORITY_MEDIUM;

    $sendingTask->scheduledAt = $this->scheduler->getScheduledTimeWithDelay(
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE),
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER)
    );
    return $sendingTask->save();
  }

  private function rescheduleAutomaticEmailSendingTask(NewsletterEntity $newsletter, ScheduledTask $task, $meta = false) {
    $sendingTask = SendingTask::createFromScheduledTask($task);
    if ($meta) {
      $sendingTask->__set('meta', $meta);
    }
    // compute new 'scheduled_at' from now
    $sendingTask->scheduledAt = $this->scheduler->getScheduledTimeWithDelay(
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE),
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER)
    );
    $sendingTask->save();
  }
}
