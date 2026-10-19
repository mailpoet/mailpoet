<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\HeldCampaignsResponseBuilder;
use MailPoet\API\JSON\ResponseBuilders\SendingQueuesResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Sending\OverdueCampaignHold;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\FreeDomains;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Carbon\Carbon;

class SendingQueue extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var MailerFactory */
  private $mailerFactory;

  /** @var NewsletterValidator */
  private $newsletterValidator;

  /** @var SettingsController */
  private $settings;

  /** @var DaemonTrigger */
  private $actionSchedulerDaemonTriggerAction;

  /** @var SendingQueuesResponseBuilder */
  private $sendingQueuesResponseBuilder;

  /** @var CronHelper */
  private $cronHelper;

  /** @var TimeZoneCampaignScheduler */
  private $timeZoneCampaignScheduler;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var FreeDomains */
  private $freeDomains;

  private OverdueCampaignHold $overdueCampaignHold;

  private HeldCampaignsResponseBuilder $heldCampaignsResponseBuilder;

  private NewsletterDeleteController $newsletterDeleteController;

  public function __construct(
    SubscribersFeature $subscribersFeature,
    NewslettersRepository $newsletterRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersFinder $subscribersFinder,
    ScheduledTasksRepository $scheduledTasksRepository,
    MailerFactory $mailerFactory,
    SettingsController $settings,
    DaemonTrigger $actionSchedulerDaemonTriggerAction,
    NewsletterValidator $newsletterValidator,
    SendingQueuesResponseBuilder $sendingQueuesResponseBuilder,
    CronHelper $cronHelper,
    TimeZoneCampaignScheduler $timeZoneCampaignScheduler,
    AuthorizedEmailsController $authorizedEmailsController,
    FreeDomains $freeDomains,
    OverdueCampaignHold $overdueCampaignHold,
    HeldCampaignsResponseBuilder $heldCampaignsResponseBuilder,
    NewsletterDeleteController $newsletterDeleteController
  ) {
    $this->subscribersFeature = $subscribersFeature;
    $this->subscribersFinder = $subscribersFinder;
    $this->newsletterRepository = $newsletterRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->mailerFactory = $mailerFactory;
    $this->settings = $settings;
    $this->actionSchedulerDaemonTriggerAction = $actionSchedulerDaemonTriggerAction;
    $this->newsletterValidator = $newsletterValidator;
    $this->sendingQueuesResponseBuilder = $sendingQueuesResponseBuilder;
    $this->cronHelper = $cronHelper;
    $this->timeZoneCampaignScheduler = $timeZoneCampaignScheduler;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->freeDomains = $freeDomains;
    $this->overdueCampaignHold = $overdueCampaignHold;
    $this->heldCampaignsResponseBuilder = $heldCampaignsResponseBuilder;
    $this->newsletterDeleteController = $newsletterDeleteController;
  }

  public function add($data = []) {
    if ($this->subscribersFeature->check()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Subscribers limit reached.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }
    $newsletterId = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );

    // check that the newsletter exists
    $newsletter = $this->newsletterRepository->findOneById($newsletterId);
    $this->newsletterRepository->prefetchOptions([$newsletter]);

    if (!$newsletter instanceof NewsletterEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }

    $validationError = $this->newsletterValidator->validate($newsletter);
    if ($validationError) {
      return $this->errorResponse([
        APIError::BAD_REQUEST => $validationError,
      ]);
    }

    try {
      // check that the sending method has been configured properly by verifying that default mailer can be build
      $this->mailerFactory->getDefaultMailer();

      $isScheduled = (bool)$newsletter->getOptionValue('isScheduled');
      if ($isScheduled && $this->timeZoneCampaignScheduler->isSubscriberTimeZoneMode($newsletter)) {
        $sendingQueue = $this->timeZoneCampaignScheduler->schedule($newsletter);
        WordPress::resetRunInterval();
        $this->triggerSending($newsletter);
        return $this->successResponse($this->sendingQueuesResponseBuilder->build($sendingQueue));
      }
      // Existing time zone batches must be reconciled regardless of the new send mode (scheduled or
      // immediate). Otherwise orphaned time zone queues survive in the DB and may later be picked up
      // by the scheduler cron, causing duplicate sends. Both calls are no-ops when the newsletter
      // has no time zone queues.
      if (!$this->timeZoneCampaignScheduler->canReplaceScheduledCampaign($newsletter)) {
        throw new \Exception(
          __('This email can no longer be edited because one or more time zone batches have already started.', 'mailpoet'),
          Response::STATUS_BAD_REQUEST
        );
      }
      $this->timeZoneCampaignScheduler->deleteScheduledCampaignQueues($newsletter);

      $sendingQueue = $this->sendingQueuesRepository->findOneByNewsletterAndTaskStatus($newsletter, null);

      if ($sendingQueue instanceof SendingQueueEntity) {
        return $this->errorResponse([
          APIError::NOT_FOUND => __('This newsletter is already being sent.', 'mailpoet'),
        ]);
      }

      $sendingQueue = $this->sendingQueuesRepository->findOneByNewsletterAndTaskStatus($newsletter, ScheduledTaskEntity::STATUS_SCHEDULED);

      if (is_null($sendingQueue)) {
        $scheduledTask = new ScheduledTaskEntity();
        $scheduledTask->setType(SendingQueueWorker::TASK_TYPE);
        $sendingQueue = new SendingQueueEntity();
        $sendingQueue->setNewsletter($newsletter);
        $sendingQueue->setTask($scheduledTask);

        $this->sendingQueuesRepository->persist($sendingQueue);
        $this->newsletterRepository->refresh($newsletter);
      } else {
        $scheduledTask = $sendingQueue->getTask();
      }

      if (!$scheduledTask instanceof ScheduledTaskEntity) {
        return $this->errorResponse([
          APIError::NOT_FOUND => __('Unable to find scheduled task associated with this newsletter.', 'mailpoet'),
        ]);
      }

      $scheduledTask->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
      $this->scheduledTasksRepository->persist($scheduledTask);
      $this->scheduledTasksRepository->flush();

      WordPress::resetRunInterval();
      if ($isScheduled) {
        // set newsletter status
        $newsletter->setStatus(NewsletterEntity::STATUS_SCHEDULED);

        // set scheduled task status
        $scheduledTask->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $scheduledTask->setScheduledAt(new Carbon($newsletter->getOptionValue('scheduledAt')));
      } else {
        $segments = $newsletter->getSegmentIds();

        $this->scheduledTasksRepository->refresh($scheduledTask);
        $this->subscribersFinder->addSubscribersToTaskFromSegments($scheduledTask, $segments, $newsletter->getFilterSegmentId());
        $subscribersCount = $scheduledTask->getSubscribers()->count();

        if (!$subscribersCount) {
          return $this->errorResponse([
            APIError::UNKNOWN => __('There are no subscribers in that list!', 'mailpoet'),
          ]);
        }

        $this->sendingQueuesRepository->updateCounts($sendingQueue);
        $scheduledTask->setStatus(null);
        $scheduledTask->setScheduledAt(Carbon::now()->millisecond(0));

        // set newsletter status
        $newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
      }
      // A held run is replaced by this send. It is deleted only here, after every check that can refuse the
      // request. The time zone branch above replaces held batches itself, inside its transaction.
      $this->deleteHeldCampaignQueues($newsletter);
      $this->scheduledTasksRepository->persist($scheduledTask);
      $this->newsletterRepository->flush();

      $this->triggerSending($newsletter);
      return $this->successResponse(
        ($newsletter->getLatestQueue() instanceof SendingQueueEntity) ? $this->sendingQueuesResponseBuilder->build($newsletter->getLatestQueue()) : null
      );
    } catch (\Exception $e) {
      $errorCode = APIError::UNKNOWN;
      $statusCode = Response::STATUS_NOT_FOUND;
      if ($e->getCode() === Response::STATUS_FORBIDDEN) {
        $errorCode = APIError::FORBIDDEN;
        $statusCode = Response::STATUS_FORBIDDEN;
      } elseif ($e->getCode() === Response::STATUS_BAD_REQUEST) {
        $errorCode = APIError::BAD_REQUEST;
        $statusCode = Response::STATUS_BAD_REQUEST;
      }
      return $this->errorResponse([
        $errorCode => $e->getMessage(),
      ], [], $statusCode);
    }
  }

  public function pause($data = []) {
    $newsletterId = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );
    $newsletter = $this->newsletterRepository->findOneById($newsletterId);

    if ($newsletter instanceof NewsletterEntity) {
      $queue = $newsletter->getLastUpdatedQueue();

      if (!$queue instanceof SendingQueueEntity) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('This newsletter has not been sent yet.', 'mailpoet'),
        ]);
      } else {
        if ($this->timeZoneCampaignScheduler->isTimeZoneQueue($queue)) {
          $this->timeZoneCampaignScheduler->pauseCampaign($queue);
        } else {
          $this->sendingQueuesRepository->pause($queue);
        }
        return $this->successResponse();
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function resume($data = []) {
    if ($this->subscribersFeature->check()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Subscribers limit reached.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }
    $newsletterId = (isset($data['newsletter_id'])
      ? (int)$data['newsletter_id']
      : false
    );
    $newsletter = $this->newsletterRepository->findOneById($newsletterId);

    if (!$newsletter instanceof NewsletterEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
    $failure = $this->resumeNewsletter($newsletter, $this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter));
    if ($failure) {
      return $this->errorResponse([$failure['code'] => $failure['message']], [], $failure['status']);
    }
    $this->triggerSending($newsletter);
    return $this->successResponse();
  }

  public function cancelHeld($data = []) {
    $newsletter = $this->newsletterRepository->findOneById(isset($data['newsletter_id']) ? (int)$data['newsletter_id'] : false);
    if (!$newsletter instanceof NewsletterEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
    $failure = $this->cancelHeldNewsletter($newsletter, $this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter));
    if ($failure) {
      return $this->errorResponse([$failure['code'] => $failure['message']], [], $failure['status']);
    }
    return $this->successResponse();
  }

  public function resumeAllHeld($data = []) {
    $newsletterIds = $this->getNewsletterIds($data);
    if ($newsletterIds === null) {
      return $this->invalidNewsletterIdsResponse();
    }
    $isLimitReached = $this->subscribersFeature->check();
    $resumedNewsletter = null;
    $result = $this->processHeldNewsletters($newsletterIds, function(NewsletterEntity $newsletter, array $heldTasks) use ($isLimitReached, &$resumedNewsletter) {
      if ($isLimitReached) {
        return ['message' => __('Subscribers limit reached.', 'mailpoet')];
      }
      $failure = $this->resumeNewsletter($newsletter, $heldTasks);
      if (!$failure && $newsletter->getStatus() === NewsletterEntity::STATUS_SENDING) {
        $resumedNewsletter = $newsletter;
      }
      return $failure;
    });
    if ($resumedNewsletter) {
      $this->triggerSending($resumedNewsletter);
    }
    return $this->successResponse($result);
  }

  public function cancelAllHeld($data = []) {
    $newsletterIds = $this->getNewsletterIds($data);
    if ($newsletterIds === null) {
      return $this->invalidNewsletterIdsResponse();
    }
    return $this->successResponse($this->processHeldNewsletters($newsletterIds, function(NewsletterEntity $newsletter, array $heldTasks) {
      return $this->cancelHeldNewsletter($newsletter, $heldTasks);
    }));
  }

  public function getHeld() {
    $this->overdueCampaignHold->holdDueCampaigns();
    return $this->successResponse($this->heldCampaignsResponseBuilder->buildAll($this->overdueCampaignHold->getHeld()));
  }

  public function pingCron() {
    try {
      $cronPingResponse = $this->cronHelper->pingDaemon();
    } catch (\Exception $e) {
      return $this->errorResponse([
        APIError::UNKNOWN => $e->getMessage(),
      ]);
    }
    if (!$this->cronHelper->validatePingResponse($cronPingResponse)) {
      return $this->errorResponse([
        APIError::UNKNOWN => $cronPingResponse,
      ]);
    }
    return $this->successResponse();
  }

  /**
   * @return int[]|null Null when the ids are missing or invalid.
   */
  private function getNewsletterIds(array $data): ?array {
    $ids = $data['newsletter_ids'] ?? null;
    if (!is_array($ids) || !$ids) {
      return null;
    }
    foreach ($ids as $id) {
      if (!(is_int($id) || (is_string($id) && ctype_digit($id))) || (int)$id < 1) {
        return null;
      }
    }
    return array_values(array_unique(array_map('intval', $ids)));
  }

  private function invalidNewsletterIdsResponse() {
    return $this->badRequest([
      APIError::BAD_REQUEST => __('Choose at least one email.', 'mailpoet'),
    ]);
  }

  /**
   * Runs $process for each requested email that is still held. The held tasks of all emails are loaded in one
   * query. An email that fails with an unexpected error is reported without the error text.
   *
   * @param int[] $newsletterIds
   * @param callable(NewsletterEntity, ScheduledTaskEntity[]): (array{message: string}|null) $process Returns a failure, or null on success.
   * @return array{done: int[], failed: array<array{id: int, message: string}>, skipped: int[]}
   */
  private function processHeldNewsletters(array $newsletterIds, callable $process): array {
    /** @var array<int, array{newsletter: NewsletterEntity, tasks: ScheduledTaskEntity[]}> $heldByNewsletter */
    $heldByNewsletter = [];
    foreach ($this->scheduledTasksRepository->findHeldOverdueForNewsletterIds($newsletterIds) as $heldTask) {
      $heldQueue = $heldTask->getSendingQueue();
      $heldNewsletter = $heldQueue ? $heldQueue->getNewsletter() : null;
      if ($heldNewsletter instanceof NewsletterEntity) {
        $heldByNewsletter[(int)$heldNewsletter->getId()]['newsletter'] = $heldNewsletter;
        $heldByNewsletter[(int)$heldNewsletter->getId()]['tasks'][] = $heldTask;
      }
    }
    $done = [];
    $failed = [];
    $skipped = [];
    foreach ($newsletterIds as $id) {
      if (!isset($heldByNewsletter[$id])) {
        $skipped[] = $id;
        continue;
      }
      try {
        $failure = $process($heldByNewsletter[$id]['newsletter'], $heldByNewsletter[$id]['tasks']);
      } catch (\Exception $e) {
        $failure = ['message' => $this->getUnexpectedErrorMessage()];
      }
      if ($failure) {
        $failed[] = ['id' => $id, 'message' => $failure['message']];
      } else {
        $done[] = $id;
      }
    }
    return ['done' => $done, 'failed' => $failed, 'skipped' => $skipped];
  }

  private function getUnexpectedErrorMessage(): string {
    return __('This email couldn’t be updated. Reload the page and try again.', 'mailpoet');
  }

  /**
   * Every check that can refuse runs before the first change, so a refused email is left exactly as it was.
   *
   * @param ScheduledTaskEntity[] $heldTasks
   * @return array{code: string, message: string, status: int}|null Null when the newsletter was resumed.
   */
  private function resumeNewsletter(NewsletterEntity $newsletter, array $heldTasks): ?array {
    // Holds change only the task, so the held queues are not necessarily the last updated one.
    $queue = $heldTasks ? $heldTasks[0]->getSendingQueue() : $newsletter->getLastUpdatedQueue();
    if (!$queue instanceof SendingQueueEntity) {
      return ['code' => APIError::UNKNOWN, 'message' => __('This newsletter has not been sent yet.', 'mailpoet'), 'status' => Response::STATUS_NOT_FOUND];
    }
    if ($heldTasks && $this->hasParentSender($newsletter) && $this->scheduledTasksRepository->hasActiveSiblingIssue($newsletter)) {
      return [
        'code' => APIError::BAD_REQUEST,
        'message' => __('Another issue of this post notification is being sent. Wait until it finishes, then send this one.', 'mailpoet'),
        'status' => Response::STATUS_BAD_REQUEST,
      ];
    }
    $isPausedForSenderDomain = $this->isPausedForSenderDomain($queue);
    // Post notification copies can't be edited, so an unmarked copy with a bad sender takes the parent's sender too.
    $isSenderValid = null;
    if (!$isPausedForSenderDomain && $this->hasParentSender($newsletter)) {
      $isSenderValid = $this->authorizedEmailsController->isSenderAddressValid($newsletter, 'resume', false);
    }
    $previousSender = [$newsletter->getSenderAddress(), $newsletter->getSenderName()];
    try {
      if ($isPausedForSenderDomain || $isSenderValid === false) {
        // The parent's sender has to be on the entity to be validated. It is only in memory until the resume below
        // saves it, and put back when the resume is refused or fails.
        $this->applyParentSender($newsletter);
        $isSenderValid = null;
      }
      if ($isSenderValid === null) {
        $isSenderValid = $this->authorizedEmailsController->isSenderAddressValid($newsletter, 'resume', $isPausedForSenderDomain);
      }
      if (!$isSenderValid) {
        $message = $this->getSenderDomainNotReadyMessage($newsletter);
        $this->restoreSender($newsletter, $previousSender);
        return ['code' => APIError::FORBIDDEN, 'message' => $message, 'status' => Response::STATUS_FORBIDDEN];
      }
    } catch (\Throwable $e) {
      $this->restoreSender($newsletter, $previousSender);
      throw $e;
    }

    // Any explicit resume is a confirmation, so the campaign is not held again for being late.
    $isTimeZoneQueue = $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue);
    if ($isTimeZoneQueue) {
      $queues = $this->timeZoneCampaignScheduler->getCampaignQueues($queue);
    } else {
      $queues = $heldTasks ? array_filter(array_map(function(ScheduledTaskEntity $task) {
        return $task->getSendingQueue();
      }, $heldTasks)) : [$queue];
    }
    $confirmedBefore = [];
    foreach ($queues as $campaignQueue) {
      $campaignTask = $campaignQueue->getTask();
      if ($campaignTask instanceof ScheduledTaskEntity) {
        $confirmedBefore[] = [$campaignTask, $campaignTask->isOverdueSendConfirmed()];
        $campaignTask->setOverdueSendConfirmed(true);
      }
    }

    // The confirmation is only in memory until the resume saves it. If the resume fails, the next email's
    // save must not confirm this one.
    try {
      if ($isTimeZoneQueue) {
        $this->timeZoneCampaignScheduler->resumeCampaign($queue);
      } else {
        foreach ($queues as $queueToResume) {
          $this->sendingQueuesRepository->resume($queueToResume);
        }
      }
    } catch (\Throwable $e) {
      foreach ($confirmedBefore as [$campaignTask, $wasConfirmed]) {
        $campaignTask->setOverdueSendConfirmed($wasConfirmed);
      }
      $this->restoreSender($newsletter, $previousSender);
      throw $e;
    }
    return null;
  }

  /**
   * @param ScheduledTaskEntity[] $heldTasks
   * @return array{code: string, message: string, status: int}|null Null when the held run was cancelled.
   */
  private function cancelHeldNewsletter(NewsletterEntity $newsletter, array $heldTasks): ?array {
    if (!$heldTasks) {
      return ['code' => APIError::BAD_REQUEST, 'message' => __('This email is not held.', 'mailpoet'), 'status' => Response::STATUS_BAD_REQUEST];
    }

    if ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $this->newsletterDeleteController->bulkDelete([(int)$newsletter->getId()]);
      return null;
    }

    $this->deleteHeldCampaignQueues($newsletter, $heldTasks);
    if ($newsletter->getType() === NewsletterEntity::TYPE_STANDARD) {
      $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
      $this->newsletterRepository->flush();
    }
    return null;
  }

  /**
   * @param ScheduledTaskEntity[]|null $heldTasks Loaded when not given.
   */
  private function deleteHeldCampaignQueues(NewsletterEntity $newsletter, ?array $heldTasks = null): void {
    $heldTasks = $heldTasks ?? $this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter);
    foreach ($heldTasks as $heldTask) {
      $heldQueue = $heldTask->getSendingQueue();
      if ($heldQueue instanceof SendingQueueEntity) {
        $this->timeZoneCampaignScheduler->deleteCampaignQueue($heldQueue);
      }
    }
    if ($heldTasks) {
      $this->scheduledTasksRepository->flush();
    }
  }

  private function isPausedForSenderDomain(SendingQueueEntity $queue): bool {
    $queues = $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue)
      ? $this->timeZoneCampaignScheduler->getCampaignQueues($queue)
      : [$queue];
    foreach ($queues as $campaignQueue) {
      $task = $campaignQueue->getTask();
      if ($task instanceof ScheduledTaskEntity && $task->getPauseReason() === ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN) {
        return true;
      }
    }
    return false;
  }

  /**
   * Notification history emails are copies made at schedule time, so they keep the parent's old sender.
   */
  private function hasParentSender(NewsletterEntity $newsletter): bool {
    return $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY && $newsletter->getParent() instanceof NewsletterEntity;
  }

  /**
   * @param array{0: string, 1: string} $sender
   */
  private function restoreSender(NewsletterEntity $newsletter, array $sender): void {
    $newsletter->setSenderAddress($sender[0]);
    $newsletter->setSenderName($sender[1]);
  }

  private function applyParentSender(NewsletterEntity $newsletter): void {
    $parent = $newsletter->getParent();
    if (!$parent instanceof NewsletterEntity || !$this->hasParentSender($newsletter)) {
      return;
    }
    $newsletter->setSenderAddress($parent->getSenderAddress());
    $newsletter->setSenderName($parent->getSenderName());
  }

  /**
   * Plain text: the client escapes API errors when rendering them.
   */
  private function getSenderDomainNotReadyMessage(NewsletterEntity $newsletter): string {
    $sender = (string)$newsletter->getSenderAddress();
    $domain = Helpers::extractEmailDomain($sender);

    if ($domain === '') {
      return __('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, send it from an authenticated sender domain, then click Resume again.', 'mailpoet');
    }
    if ($this->freeDomains->isEmailOnFreeDomain($sender)) {
      // translators: %s is a domain such as gmail.com.
      return sprintf(__('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, send it from an address on your own authenticated domain instead of %s, then click Resume again.', 'mailpoet'), $domain);
    }
    // translators: %s is a domain such as example.com.
    return sprintf(__('This email can’t be resumed yet. To make sure your campaigns reach your subscribers, authenticate %s or switch to another authenticated sender domain, then click Resume again.', 'mailpoet'), $domain);
  }

  /**
   * In case the newsletter was switched to sending trigger the background job immediately.
   * This is done so that user immediately sees that email is sending and doesn't have to wait on WP Cron to start it.
   */
  private function triggerSending(NewsletterEntity $newsletter): void {
    if (
      $newsletter->getStatus() === NewsletterEntity::STATUS_SENDING
      && $this->settings->get('cron_trigger.method') === CronTrigger::METHOD_ACTION_SCHEDULER
    ) {
      $this->actionSchedulerDaemonTriggerAction->process();
    }
  }
}
