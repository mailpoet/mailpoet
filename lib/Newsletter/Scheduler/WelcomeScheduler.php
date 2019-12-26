<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;

class WelcomeScheduler {

  const WORDPRESS_ALL_ROLES = 'mailpoet_all';

  public function scheduleSubscriberWelcomeNotification($subscriber_id, $segments) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    $result = [];
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'segment' &&
        in_array($newsletter->segment, $segments)
      ) {
        $result[] = $this->createWelcomeNotificationSendingTask($newsletter, $subscriber_id);
      }
    }
    return $result;
  }

  public function scheduleWPUserWelcomeNotification(
    $subscriber_id,
    $wp_user,
    $old_user_data = false
  ) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'user') {
        if (!empty($old_user_data['roles'])) {
          // do not schedule welcome newsletter if roles have not changed
          $old_role = $old_user_data['roles'];
          $new_role = $wp_user['roles'];
          if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
            !array_diff($old_role, $new_role)
          ) {
            continue;
          }
        }
        if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
          in_array($newsletter->role, $wp_user['roles'])
        ) {
          $this->createWelcomeNotificationSendingTask($newsletter, $subscriber_id);
        }
      }
    }
  }

  public function createWelcomeNotificationSendingTask($newsletter, $subscriber_id) {
    $previously_scheduled_notification = SendingQueue::joinWithSubscribers()
      ->where('queues.newsletter_id', $newsletter->id)
      ->where('subscribers.subscriber_id', $subscriber_id)
      ->findOne();
    if (!empty($previously_scheduled_notification)) return;
    $sending_task = SendingTask::create();
    $sending_task->newsletter_id = $newsletter->id;
    $sending_task->setSubscribers([$subscriber_id]);
    $sending_task->status = SendingQueue::STATUS_SCHEDULED;
    $sending_task->priority = SendingQueue::PRIORITY_HIGH;
    $sending_task->scheduled_at = Scheduler::getScheduledTimeWithDelay(
      $newsletter->afterTimeType,
      $newsletter->afterTimeNumber
    );
    return $sending_task->save();
  }

}
