<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler as NewsletterScheduler;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Tasks\Sending as SendingTask;

class Scheduler {
  const TASK_BATCH_SIZE = 5;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var CronHelper */
  private $cronHelper;

  public function __construct(
    SubscribersFinder $subscribersFinder,
    LoggerFactory $loggerFactory,
    CronHelper $cronHelper
  ) {
    $this->cronHelper = $cronHelper;
    $this->subscribersFinder = $subscribersFinder;
    $this->loggerFactory = $loggerFactory;
  }

  public function process($timer = false) {
    $timer = $timer ?: microtime(true);

    // abort if execution limit is reached
    $this->cronHelper->enforceExecutionLimit($timer);

    $scheduledQueues = self::getScheduledQueues();
    if (!count($scheduledQueues)) return false;
    $this->updateTasks($scheduledQueues);
    foreach ($scheduledQueues as $i => $queue) {
      $newsletter = Newsletter::findOneWithOptions($queue->newsletterId);
      if (!$newsletter || $newsletter->deletedAt !== null) {
        $queue->delete();
      } elseif ($newsletter->status !== Newsletter::STATUS_ACTIVE && $newsletter->status !== Newsletter::STATUS_SCHEDULED) {
        continue;
      } elseif ($newsletter->type === Newsletter::TYPE_WELCOME) {
        $this->processWelcomeNewsletter($newsletter, $queue);
      } elseif ($newsletter->type === Newsletter::TYPE_NOTIFICATION) {
        $this->processPostNotificationNewsletter($newsletter, $queue);
      } elseif ($newsletter->type === Newsletter::TYPE_STANDARD) {
        $this->processScheduledStandardNewsletter($newsletter, $queue);
      } elseif ($newsletter->type === Newsletter::TYPE_AUTOMATIC) {
        $this->processScheduledAutomaticEmail($newsletter, $queue);
      }
      $this->cronHelper->enforceExecutionLimit($timer);
    }
  }

  public function processWelcomeNewsletter($newsletter, $queue) {
    $subscribers = $queue->getSubscribers();
    if (empty($subscribers[0])) {
      $queue->delete();
      return false;
    }
    $subscriberId = (int)$subscribers[0];
    if ($newsletter->event === 'segment') {
      if ($this->verifyMailpoetSubscriber($subscriberId, $newsletter, $queue) === false) {
        return false;
      }
    } else {
      if ($newsletter->event === 'user') {
        if ($this->verifyWPSubscriber($subscriberId, $newsletter, $queue) === false) {
          return false;
        }
      }
    }
    $queue->status = null;
    $queue->save();
    return true;
  }

  public function processPostNotificationNewsletter($newsletter, $queue) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'process post notification in scheduler',
      ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
    );
    // ensure that segments exist
    $segments = $newsletter->segments()->findMany();
    if (empty($segments)) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
        'post notification no segments',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
      );
      return $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    }

    // ensure that subscribers are in segments

    $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments($queue->task(), $segments);

    if (empty($subscribersCount)) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
        'post notification no subscribers',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
      );
      return $this->deleteQueueOrUpdateNextRunDate($queue, $newsletter);
    }

    // create a duplicate newsletter that acts as a history record
    $notificationHistory = $this->createNotificationHistory($newsletter->id);
    if (!$notificationHistory) return false;

    // queue newsletter for delivery
    $queue->newsletterId = $notificationHistory->id;
    $queue->status = null;
    $queue->save();
    // update notification status
    $notificationHistory->setStatus(Newsletter::STATUS_SENDING);
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'post notification set status to sending',
      ['newsletter_id' => $newsletter->id, 'task_id' => $queue->taskId]
    );
    return true;
  }

  public function processScheduledAutomaticEmail($newsletter, $queue) {
    if ($newsletter->sendTo === 'segment') {
      $segment = Segment::findOne($newsletter->segment);
      $result = $this->subscribersFinder->addSubscribersToTaskFromSegments($queue->task(), [$segment]);
      if (empty($result)) {
        $queue->delete();
        return false;
      }
    } else {
      $subscribers = $queue->getSubscribers();
      $subscriber = (!empty($subscribers) && is_array($subscribers)) ?
        Subscriber::findOne($subscribers[0]) :
        false;
      if (!$subscriber) {
        $queue->delete();
        return false;
      }
      if ($this->verifySubscriber($subscriber, $queue) === false) {
        return false;
      }
    }

    $queue->status = null;
    $queue->save();
    return true;
  }

  public function processScheduledStandardNewsletter($newsletter, SendingTask $task) {
    $segments = $newsletter->segments()->findMany();
    $this->subscribersFinder->addSubscribersToTaskFromSegments($task->task(), $segments);
    // update current queue
    $task->updateCount();
    $task->status = null;
    $task->save();
    // update newsletter status
    $newsletter->setStatus(Newsletter::STATUS_SENDING);
    return true;
  }

  public function verifyMailpoetSubscriber($subscriberId, $newsletter, $queue) {
    $subscriber = Subscriber::findOne($subscriberId);
    // check if subscriber is in proper segment
    $subscriberInSegment =
      SubscriberSegment::where('subscriber_id', $subscriberId)
        ->where('segment_id', $newsletter->segment)
        ->where('status', 'subscribed')
        ->findOne();
    if (!$subscriber || !$subscriberInSegment) {
      $queue->delete();
      return false;
    }
    return $this->verifySubscriber($subscriber, $queue);
  }

  public function verifyWPSubscriber($subscriberId, $newsletter, $queue) {
    // check if user has the proper role
    $subscriber = Subscriber::findOne($subscriberId);
    if (!$subscriber || $subscriber->isWPUser() === false) {
      $queue->delete();
      return false;
    }
    $wpUser = get_userdata($subscriber->wpUserId);
    if ($wpUser === false) {
      $queue->delete();
      return false;
    }
    if ($newsletter->role !== WelcomeScheduler::WORDPRESS_ALL_ROLES
      && !in_array($newsletter->role, ((array)$wpUser)['roles'])
    ) {
      $queue->delete();
      return false;
    }
    return $this->verifySubscriber($subscriber, $queue);
  }

  public function verifySubscriber($subscriber, $queue) {
    if ($subscriber->status === Subscriber::STATUS_UNCONFIRMED) {
      // reschedule delivery
      $queue->rescheduleProgressively();
      return false;
    } else if ($subscriber->status === Subscriber::STATUS_UNSUBSCRIBED) {
      $queue->delete();
      return false;
    }
    return true;
  }

  public function deleteQueueOrUpdateNextRunDate($queue, $newsletter) {
    if ($newsletter->intervalType === PostNotificationScheduler::INTERVAL_IMMEDIATELY) {
      $queue->delete();
      return;
    } else {
      $nextRunDate = NewsletterScheduler::getNextRunDate($newsletter->schedule);
      if (!$nextRunDate) {
        $queue->delete();
        return;
      }
      $queue->scheduledAt = $nextRunDate;
      $queue->save();
    }
  }

  public function createNotificationHistory($newsletterId) {
    $newsletter = Newsletter::findOne($newsletterId);
    if (!$newsletter instanceof Newsletter) {
      return false;
    }
    $notificationHistory = $newsletter->createNotificationHistory();
    return ($notificationHistory->getErrors() === false) ?
      $notificationHistory :
      false;
  }

  private function updateTasks(array $scheduledQueues) {
    $ids = array_map(function ($queue) {
      return $queue->taskId;
    }, $scheduledQueues);
    ScheduledTask::touchAllByIds($ids);
  }

  public static function getScheduledQueues() {
    return SendingTask::getScheduledQueues(self::TASK_BATCH_SIZE);
  }
}
