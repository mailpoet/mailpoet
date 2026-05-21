<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Carbon\Carbon;

/**
 * Centralized newsletter status-toggle (active/draft) flow used by the REST
 * endpoint at `PUT mailpoet/v1/newsletters/{id}/status`.
 *
 * Lifts the status change, paused-queue resume, post-notification reschedule,
 * and authorized-sender gating into one place so the REST endpoint can stay a
 * thin HTTP adapter.
 */
class StatusController {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var NewsletterValidator */
  private $newsletterValidator;

  /** @var Scheduler */
  private $scheduler;

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SubscribersFeature $subscribersFeature,
    AuthorizedEmailsController $authorizedEmailsController,
    NewsletterValidator $newsletterValidator,
    Scheduler $scheduler,
    PostNotificationScheduler $postNotificationScheduler
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->subscribersFeature = $subscribersFeature;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->newsletterValidator = $newsletterValidator;
    $this->scheduler = $scheduler;
    $this->postNotificationScheduler = $postNotificationScheduler;
  }

  /**
   * @throws BulkActionException
   */
  public function setStatus(NewsletterEntity $newsletter, string $status): NewsletterEntity {
    if ($status === NewsletterEntity::STATUS_ACTIVE && $this->subscribersFeature->check()) {
      throw new BulkActionException(
        __('Subscribers limit reached.', 'mailpoet'),
        'mailpoet_newsletters_subscribers_limit',
        403
      );
    }
    if ($status === NewsletterEntity::STATUS_ACTIVE && !$this->authorizedEmailsController->isSenderAddressValid($newsletter)) {
      throw new BulkActionException(
        __('The sender address is not an authorized sender domain.', 'mailpoet'),
        'mailpoet_newsletters_unauthorized_sender',
        403
      );
    }
    if ($status === NewsletterEntity::STATUS_ACTIVE) {
      $validationError = $this->newsletterValidator->validate($newsletter);
      if ($validationError !== null) {
        throw new BulkActionException(
          $validationError,
          'mailpoet_newsletters_validation_failed',
          403
        );
      }
    }

    $this->newslettersRepository->prefetchOptions([$newsletter]);
    $newsletter->setStatus($status);

    if ($newsletter->getStatus() === NewsletterEntity::STATUS_ACTIVE) {
      // Unpause any tasks that were halted by an earlier deactivation so a
      // toggle-off/on cycle resumes rather than orphaning the queue.
      foreach ($newsletter->getUnfinishedQueues() as $queue) {
        $task = $queue->getTask();
        if ($task && $task->getStatus() === ScheduledTaskEntity::STATUS_PAUSED) {
          $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        }
      }
    }

    // Past-due post notifications need their next run date computed and a
    // new sending task scheduled; otherwise re-activation would silently
    // miss the next interval.
    if ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION && $status === NewsletterEntity::STATUS_ACTIVE) {
      $scheduleOption = $newsletter->getOption(NewsletterOptionFieldEntity::NAME_SCHEDULE);
      if ($scheduleOption === null) {
        throw new BulkActionException(
          __('This email has incorrect state.', 'mailpoet'),
          'mailpoet_newsletters_missing_schedule',
          400
        );
      }
      $nextRunDate = $this->scheduler->getNextRunDate($scheduleOption->getValue());
      foreach ($newsletter->getQueues() as $queue) {
        $task = $queue->getTask();
        if (
          $task
          && $task->getScheduledAt() <= Carbon::now()->millisecond(0)
          && $task->getStatus() === SendingQueueEntity::STATUS_SCHEDULED
        ) {
          $parsedDate = $nextRunDate ? Carbon::createFromFormat('Y-m-d H:i:s', $nextRunDate) : null;
          if ($parsedDate === false) {
            throw InvalidStateException::create()->withMessage('Invalid next run date generated');
          }
          $task->setScheduledAt($parsedDate);
        }
      }
      $this->postNotificationScheduler->createPostNotificationSendingTask($newsletter);
    }

    $this->newslettersRepository->flush();
    return $newsletter;
  }
}
