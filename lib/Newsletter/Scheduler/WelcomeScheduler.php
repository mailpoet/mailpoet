<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;

class WelcomeScheduler {

  const WORDPRESS_ALL_ROLES = 'mailpoet_all';

  public function scheduleSubscriberWelcomeNotification($subscriberId, $segments) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    $result = [];
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'segment' &&
        in_array($newsletter->segment, $segments)
      ) {
        $result[] = $this->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
      }
    }
    return $result;
  }

  public function scheduleWPUserWelcomeNotification(
    $subscriberId,
    $wpUser,
    $oldUserData = false
  ) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'user') {
        if (!empty($oldUserData['roles'])) {
          // do not schedule welcome newsletter if roles have not changed
          $oldRole = $oldUserData['roles'];
          $newRole = $wpUser['roles'];
          if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
            !array_diff($oldRole, $newRole)
          ) {
            continue;
          }
        }
        if ($newsletter->role === self::WORDPRESS_ALL_ROLES ||
          in_array($newsletter->role, $wpUser['roles'])
        ) {
          $this->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
        }
      }
    }
  }

  public function createWelcomeNotificationSendingTask($newsletter, $subscriberId) {
    $previouslyScheduledNotification = SendingQueue::joinWithSubscribers()
      ->where('queues.newsletter_id', $newsletter->id)
      ->where('subscribers.subscriber_id', $subscriberId)
      ->findOne();
    if (!empty($previouslyScheduledNotification)) return;
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->id;
    $sendingTask->setSubscribers([$subscriberId]);
    $sendingTask->status = SendingQueue::STATUS_SCHEDULED;
    $sendingTask->priority = SendingQueue::PRIORITY_HIGH;
    $sendingTask->scheduledAt = Scheduler::getScheduledTimeWithDelay(
      $newsletter->afterTimeType,
      $newsletter->afterTimeNumber
    );
    return $sendingTask->save();
  }

}
