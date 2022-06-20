<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Posts;

class PostNotificationScheduler {

  const SECONDS_IN_HOUR = 3600;
  const LAST_WEEKDAY_FORMAT = 'L';
  const INTERVAL_DAILY = 'daily';
  const INTERVAL_IMMEDIATELY = 'immediately';
  const INTERVAL_NTHWEEKDAY = 'nthWeekDay';
  const INTERVAL_WEEKLY = 'weekly';
  const INTERVAL_IMMEDIATE = 'immediate';
  const INTERVAL_MONTHLY = 'monthly';

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  /** @var NewsletterPostsRepository */
  private $newsletterPostsRepository;

  /** @var Scheduler */
  private $scheduler;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository,
    NewsletterPostsRepository $newsletterPostsRepository,
    Scheduler $scheduler
  ) {
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
    $this->newsletterPostsRepository = $newsletterPostsRepository;
    $this->scheduler = $scheduler;
  }

  public function transitionHook($newStatus, $oldStatus, $post) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'transition post notification hook initiated',
      [
        'post_id' => $post->ID,
        'new_status' => $newStatus,
        'old_status' => $oldStatus,
      ]
    );
    $types = Posts::getTypes();
    if (($newStatus !== 'publish') || !isset($types[$post->post_type])) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }
    $this->schedulePostNotification($post->ID);
  }

  public function schedulePostNotification($postId) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'schedule post notification hook',
      ['post_id' => $postId]
    );
    $newsletters = $this->newslettersRepository->findActiveByTypes([NewsletterEntity::TYPE_NOTIFICATION]);
    $this->newslettersRepository->prefetchOptions($newsletters);
    if (!count($newsletters)) {
      return false;
    }
    foreach ($newsletters as $newsletter) {
      $post = $this->newsletterPostsRepository->findOneBy([
        'newsletter' => $newsletter,
        'postId' => $postId,
      ]);
      if ($post === null) {
        $this->createPostNotificationSendingTask($newsletter);
      }
    }
  }

  public function createPostNotificationSendingTask(NewsletterEntity $newsletter): ?SendingTask {
    $notificationHistory = $this->newslettersRepository->findSendigNotificationHistoryWithPausedTask($newsletter);
    if (count($notificationHistory) > 0) {
      return null;
    }

    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    if (!$scheduleOption) {
      return null;
    }
    $nextRunDate = $this->scheduler->getNextRunDate($scheduleOption->getValue());
    if (!$nextRunDate) {
      return null;
    }

    // do not schedule duplicate queues for the same time
    $lastQueue = $newsletter->getLatestQueue();
    $task = $lastQueue !== null ? $lastQueue->getTask() : null;
    $scheduledAt = $task !== null ? $task->getScheduledAt() : null;
    if ($scheduledAt && $scheduledAt->format('Y-m-d H:i:s') === $nextRunDate) {
      return null;
    }

    $sendingTask = SendingTask::create();
    $sendingTask->newsletterId = $newsletter->getId();
    $sendingTask->status = SendingQueueEntity::STATUS_SCHEDULED;
    $sendingTask->scheduledAt = $nextRunDate;
    $sendingTask->save();
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'schedule post notification',
      ['sending_task' => $sendingTask->id(), 'scheduled_at' => $nextRunDate]
    );
    return $sendingTask;
  }

  public function processPostNotificationSchedule(NewsletterEntity $newsletter) {
    $intervalTypeOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_INTERVAL_TYPE);
    $intervalType = $intervalTypeOption ? $intervalTypeOption->getValue() : null;

    $timeOfDayOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_TIME_OF_DAY);
    $hour = $timeOfDayOption ? (int)$timeOfDayOption->getValue() / self::SECONDS_IN_HOUR : null;

    $weekDayOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_WEEK_DAY);
    $weekDay = $weekDayOption ? $weekDayOption->getValue() : null;

    $monthDayOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_MONTH_DAY);
    $monthDay = $monthDayOption ? $monthDayOption->getValue() : null;

    $nthWeekDayOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_NTH_WEEK_DAY);
    $nthWeekDay = $nthWeekDayOption ? $nthWeekDayOption->getValue() : null;
    $nthWeekDay = ($nthWeekDay === self::LAST_WEEKDAY_FORMAT) ? $nthWeekDay : '#' . $nthWeekDay;
    switch ($intervalType) {
      case self::INTERVAL_IMMEDIATE:
      case self::INTERVAL_DAILY:
        $schedule = sprintf('0 %s * * *', $hour);
        break;
      case self::INTERVAL_WEEKLY:
        $schedule = sprintf('0 %s * * %s', $hour, $weekDay);
        break;
      case self::INTERVAL_NTHWEEKDAY:
        $schedule = sprintf('0 %s ? * %s%s', $hour, $weekDay, $nthWeekDay);
        break;
      case self::INTERVAL_MONTHLY:
        $schedule = sprintf('0 %s %s * *', $hour, $monthDay);
        break;
      case self::INTERVAL_IMMEDIATELY:
      default:
        $schedule = '* * * * *';
        break;
    }
    $optionField = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => NewsletterOptionFieldEntity::NAME_SCHEDULE,
    ]);
    if (!$optionField instanceof NewsletterOptionFieldEntity) {
      throw new \Exception('NewsletterOptionField for schedule doesn’t exist.');
    }
    $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
    if ($scheduleOption === null) {
      $scheduleOption = new NewsletterOptionEntity($newsletter, $optionField);
      $newsletter->getOptions()->add($scheduleOption);
    }
    $scheduleOption->setValue($schedule);
    $this->newsletterOptionsRepository->persist($scheduleOption);
    $this->newsletterOptionsRepository->flush();
    return $scheduleOption->getValue();
  }
}
