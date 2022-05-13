<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;

class WelcomeScheduler {

  const WORDPRESS_ALL_ROLES = 'mailpoet_all';

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var Scheduler  */
  private $scheduler;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository,
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    Scheduler $scheduler
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->scheduler = $scheduler;
  }

  public function scheduleSubscriberWelcomeNotification($subscriberId, $segments) {
    $newsletters = $this->newslettersRepository->findActiveByTypes([NewsletterEntity::TYPE_WELCOME]);
    if (empty($newsletters)) return false;
    $result = [];
    foreach ($newsletters as $newsletter) {
      if (
        $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) === 'segment' &&
        in_array($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SEGMENT), $segments)
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
    $newsletters = $this->newslettersRepository->findActiveByTypes([NewsletterEntity::TYPE_WELCOME]);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== 'user') {
        continue;
      }
      $newsletterRole = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_ROLE);
      if (!empty($oldUserData['roles'])) {
        // do not schedule welcome newsletter if roles have not changed
        $oldRole = $oldUserData['roles'];
        $newRole = $wpUser['roles'];
        if (
          $newsletterRole === self::WORDPRESS_ALL_ROLES ||
          !array_diff($newRole, $oldRole)
        ) {
          continue;
        }
      }
      if (
        $newsletterRole === self::WORDPRESS_ALL_ROLES ||
        in_array($newsletterRole, $wpUser['roles'])
      ) {
        $this->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
      }
    }
  }

  public function createWelcomeNotificationSendingTask(NewsletterEntity $newsletter, $subscriberId) {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!($subscriber instanceof SubscriberEntity) || $subscriber->getDeletedAt() !== null) {
      return;
    }
    if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) === 'segment') {
      $segment = $this->segmentsRepository->findOneById((int)$newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SEGMENT));
      if ((!$segment instanceof SegmentEntity) || $segment->getDeletedAt() !== null) {
        return;
      }
    }
    if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) === 'user') {
      $segment = $this->segmentsRepository->getWPUsersSegment();
      if ((!$segment instanceof SegmentEntity) || $segment->getDeletedAt() !== null) {
        return;
      }
    }
    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, $subscriberId);
    if (!empty($previouslyScheduledNotification)) {
      return;
    }
    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->getId();
    $sendingTask->setSubscribers([$subscriberId]);
    $sendingTask->status = SendingQueueEntity::STATUS_SCHEDULED;
    $sendingTask->priority = SendingQueueEntity::PRIORITY_HIGH;
    $sendingTask->scheduledAt = $this->scheduler->getScheduledTimeWithDelay(
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE),
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER)
    );
    return $sendingTask->save();
  }
}
