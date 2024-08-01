<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\UnexpectedValueException;

class DataInconsistencyController {
  const ORPHANED_SENDING_TASKS = 'orphaned_sending_tasks';
  const ORPHANED_SENDING_TASK_SUBSCRIBERS = 'orphaned_sending_task_subscribers';
  const SENDING_QUEUE_WITHOUT_NEWSLETTER = 'sending_queue_without_newsletter';
  const ORPHANED_SUBSCRIPTIONS = 'orphaned_subscriptions';
  const ORPHANED_LINKS = 'orphaned_links';
  const ORPHANED_NEWSLETTER_POSTS = 'orphaned_newsletter_posts';

  const SUPPORTED_INCONSISTENCY_CHECKS = [
    self::ORPHANED_SENDING_TASKS,
    self::ORPHANED_SENDING_TASK_SUBSCRIBERS,
    self::SENDING_QUEUE_WITHOUT_NEWSLETTER,
    self::ORPHANED_SUBSCRIPTIONS,
    self::ORPHANED_LINKS,
    self::ORPHANED_NEWSLETTER_POSTS,
  ];

  private DataInconsistencyRepository $repository;

  public function __construct(
    DataInconsistencyRepository $repository
  ) {
    $this->repository = $repository;
  }

  public function getInconsistentDataStatus(): array {
    $result = [
      self::ORPHANED_SENDING_TASKS => $this->repository->getOrphanedSendingTasksCount(),
      self::ORPHANED_SENDING_TASK_SUBSCRIBERS => $this->repository->getOrphanedScheduledTasksSubscribersCount(),
      self::SENDING_QUEUE_WITHOUT_NEWSLETTER => $this->repository->getSendingQueuesWithoutNewsletterCount(),
      self::ORPHANED_SUBSCRIPTIONS => $this->repository->getOrphanedSubscriptionsCount(),
      self::ORPHANED_LINKS => $this->repository->getOrphanedNewsletterLinksCount(),
      self::ORPHANED_NEWSLETTER_POSTS => $this->repository->getOrphanedNewsletterPostsCount(),
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
    } elseif ($inconsistency === self::SENDING_QUEUE_WITHOUT_NEWSLETTER) {
      $this->repository->cleanupSendingQueuesWithoutNewsletter();
    } elseif ($inconsistency === self::ORPHANED_SUBSCRIPTIONS) {
      $this->repository->cleanupOrphanedSubscriptions();
    } elseif ($inconsistency === self::ORPHANED_LINKS) {
      $this->repository->cleanupOrphanedNewsletterLinks();
    } elseif ($inconsistency === self::ORPHANED_NEWSLETTER_POSTS) {
      $this->repository->cleanupOrphanedNewsletterPosts();
    }
  }
}
