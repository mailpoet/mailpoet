<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Mailer\MigrationSendingPauser;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoet\UnexpectedValueException;

class DataInconsistencyController {
  const ORPHANED_SENDING_TASKS = 'orphaned_sending_tasks';
  const ORPHANED_SENDING_TASK_SUBSCRIBERS = 'orphaned_sending_task_subscribers';
  const ORPHANED_SENDING_TASK_QUEUED_SUBSCRIBERS = 'orphaned_sending_task_queued_subscribers';
  const SENDING_QUEUE_WITHOUT_NEWSLETTER = 'sending_queue_without_newsletter';
  const ORPHANED_SUBSCRIPTIONS = 'orphaned_subscriptions';
  const ORPHANED_LINKS = 'orphaned_links';
  const ORPHANED_NEWSLETTER_POSTS = 'orphaned_newsletter_posts';
  const COMPLETED_SENDING_TASK_WITH_QUEUED_SUBSCRIBERS = 'completed_sending_task_with_queued_subscribers';
  const UNMIGRATED_SENDING_TASK_SUBSCRIBERS = 'unmigrated_sending_task_subscribers';

  const SUPPORTED_INCONSISTENCY_CHECKS = [
    self::ORPHANED_SENDING_TASKS,
    self::ORPHANED_SENDING_TASK_SUBSCRIBERS,
    self::ORPHANED_SENDING_TASK_QUEUED_SUBSCRIBERS,
    self::SENDING_QUEUE_WITHOUT_NEWSLETTER,
    self::ORPHANED_SUBSCRIPTIONS,
    self::ORPHANED_LINKS,
    self::ORPHANED_NEWSLETTER_POSTS,
    self::COMPLETED_SENDING_TASK_WITH_QUEUED_SUBSCRIBERS,
    self::UNMIGRATED_SENDING_TASK_SUBSCRIBERS,
  ];

  private DataInconsistencyRepository $repository;
  private ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover;
  private MigrationSendingPauser $migrationSendingPauser;

  public function __construct(
    DataInconsistencyRepository $repository,
    ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover,
    MigrationSendingPauser $migrationSendingPauser
  ) {
    $this->repository = $repository;
    $this->scheduledTaskSubscriberMover = $scheduledTaskSubscriberMover;
    $this->migrationSendingPauser = $migrationSendingPauser;
  }

  public function getInconsistentDataStatus(): array {
    $result = [
      self::ORPHANED_SENDING_TASKS => $this->repository->getOrphanedSendingTasksCount(),
      self::ORPHANED_SENDING_TASK_SUBSCRIBERS => $this->repository->getOrphanedScheduledTasksSubscribersCount(),
      self::ORPHANED_SENDING_TASK_QUEUED_SUBSCRIBERS => $this->repository->getOrphanedScheduledTaskQueuedSubscribersCount(),
      self::SENDING_QUEUE_WITHOUT_NEWSLETTER => $this->repository->getSendingQueuesWithoutNewsletterCount(),
      self::ORPHANED_SUBSCRIPTIONS => $this->repository->getOrphanedSubscriptionsCount(),
      self::ORPHANED_LINKS => $this->repository->getOrphanedNewsletterLinksCount(),
      self::ORPHANED_NEWSLETTER_POSTS => $this->repository->getOrphanedNewsletterPostsCount(),
      self::COMPLETED_SENDING_TASK_WITH_QUEUED_SUBSCRIBERS => $this->repository->getCompletedSendingTasksWithQueuedSubscribersCount(),
      self::UNMIGRATED_SENDING_TASK_SUBSCRIBERS => $this->repository->getUnmigratedSendingTaskSubscribersCount(),
    ];
    $result['total'] = array_sum($result);
    return $result;
  }

  public function fixInconsistentData(string $inconsistency): void {
    if (!in_array($inconsistency, self::SUPPORTED_INCONSISTENCY_CHECKS, true)) {
      throw new UnexpectedValueException(__('Unsupported data inconsistency check.', 'mailpoet'));
    }
    if ($inconsistency === self::ORPHANED_SENDING_TASKS) {
      $this->repository->cleanupOrphanedSendingTasks();
    } elseif ($inconsistency === self::ORPHANED_SENDING_TASK_SUBSCRIBERS) {
      $this->repository->cleanupOrphanedScheduledTaskSubscribers();
    } elseif ($inconsistency === self::ORPHANED_SENDING_TASK_QUEUED_SUBSCRIBERS) {
      $this->repository->cleanupOrphanedScheduledTaskQueuedSubscribers();
    } elseif ($inconsistency === self::SENDING_QUEUE_WITHOUT_NEWSLETTER) {
      $this->repository->cleanupSendingQueuesWithoutNewsletter();
    } elseif ($inconsistency === self::ORPHANED_SUBSCRIPTIONS) {
      $this->repository->cleanupOrphanedSubscriptions();
    } elseif ($inconsistency === self::ORPHANED_LINKS) {
      $this->repository->cleanupOrphanedNewsletterLinks();
    } elseif ($inconsistency === self::ORPHANED_NEWSLETTER_POSTS) {
      $this->repository->cleanupOrphanedNewsletterPosts();
    } elseif ($inconsistency === self::COMPLETED_SENDING_TASK_WITH_QUEUED_SUBSCRIBERS) {
      $this->repository->reopenCompletedSendingTasksWithQueuedSubscribers();
    } elseif ($inconsistency === self::UNMIGRATED_SENDING_TASK_SUBSCRIBERS) {
      // Finish the queue backfill an interrupted migration left undone, then lift
      // the migration pause it never reached resume() to clear (a no-op if we
      // didn't pause). resume() restores the pre-migration sending state.
      $this->scheduledTaskSubscriberMover->backfillPendingToQueue($this->repository->getUnmigratedSendingTaskIds());
      $this->migrationSendingPauser->resume();
    }
  }
}
