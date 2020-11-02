<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;

class WelcomeScheduler {

  const WORDPRESS_ALL_ROLES = 'mailpoet_all';

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function scheduleSubscriberWelcomeNotification($subscriberId, $segments) {
    $newsletters = Scheduler::getNewsletters(Newsletter::TYPE_WELCOME);
    if (empty($newsletters)) return false;
    $result = [];
    foreach ($newsletters as $newsletter) {
      if ($newsletter->event === 'segment' &&
        in_array($newsletter->segment, $segments)
      ) {
        $sendingTask = $this->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
        if ($sendingTask) {
          $result[] = $sendingTask;
        }
      }
    }
    return $result ?: false;
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
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!($subscriber instanceof SubscriberEntity) || $subscriber->getDeletedAt() !== null) {
      return;
    }
    if ($newsletter->event === 'segment') {
      $segment = $this->segmentsRepository->findOneById((int)$newsletter->segment);
      if ((!$segment instanceof SegmentEntity) || $segment->getDeletedAt() !== null) {
        return;
      }
    }
    if ($newsletter->event === 'user') {
      $segment = $this->segmentsRepository->getWPUsersSegment();
      if ((!$segment instanceof SegmentEntity) || $segment->getDeletedAt() !== null) {
        return;
      }
    }
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
