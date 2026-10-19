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
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
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

  private NewsletterOptionsRepository $newsletterOptionsRepository;

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
    NewsletterDeleteController $newsletterDeleteController,
    NewsletterOptionsRepository $newsletterOptionsRepository
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
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
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

      // A held run is replaced by this send. It runs after every check that can refuse the request (a
      // refused add deletes nothing) and before the time zone branch, which returns early.
      $this->deleteHeldCampaignQueues($newsletter);

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

    if ($newsletter instanceof NewsletterEntity) {
      // Holds change only the task, so the held queues are not necessarily the last updated one.
      $heldTasks = $this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter);
      $queue = $heldTasks ? $heldTasks[0]->getSendingQueue() : $newsletter->getLastUpdatedQueue();
      if (!$queue instanceof SendingQueueEntity) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('This newsletter has not been sent yet.', 'mailpoet'),
        ]);
      } else {
        // Checked before anything is confirmed, so a refused resume leaves the task held.
        if ($heldTasks && $this->hasParentSender($newsletter) && $this->scheduledTasksRepository->hasActiveSiblingIssue($newsletter)) {
          return $this->errorResponse([
            APIError::BAD_REQUEST => __('Another issue of this post notification is being sent. Wait until it finishes, then send this one.', 'mailpoet'),
          ], [], Response::STATUS_BAD_REQUEST);
        }
        $isPausedForSenderDomain = $this->isPausedForSenderDomain($queue);
        // Post notification copies can't be edited, so an unmarked copy with a bad sender takes the parent's sender too.
        $isSenderValid = null;
        if (!$isPausedForSenderDomain && $this->hasParentSender($newsletter)) {
          $isSenderValid = $this->authorizedEmailsController->isSenderAddressValid($newsletter, 'resume', false);
        }
        if ($isPausedForSenderDomain || $isSenderValid === false) {
          $this->syncSenderFromParent($newsletter);
          $isSenderValid = null;
        }
        if ($isSenderValid === null) {
          $isSenderValid = $this->authorizedEmailsController->isSenderAddressValid($newsletter, 'resume', $isPausedForSenderDomain);
        }
        if (!$isSenderValid) {
          return $this->errorResponse([
            APIError::FORBIDDEN => $this->getSenderDomainNotReadyMessage($newsletter),
          ], [], Response::STATUS_FORBIDDEN);
        }

        // Any explicit resume is a confirmation, so the campaign is not held again for being late.
        $isTimeZoneQueue = $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue);
        $queues = $isTimeZoneQueue ? $this->timeZoneCampaignScheduler->getCampaignQueues($queue) : [$queue];
        foreach ($queues as $campaignQueue) {
          $campaignTask = $campaignQueue->getTask();
          if ($campaignTask instanceof ScheduledTaskEntity) {
            $campaignTask->setOverdueSendConfirmed(true);
          }
        }

        if ($isTimeZoneQueue) {
          $this->timeZoneCampaignScheduler->resumeCampaign($queue);
        } else {
          $queuesToResume = $heldTasks ? array_filter(array_map(function(ScheduledTaskEntity $task) {
            return $task->getSendingQueue();
          }, $heldTasks)) : [$queue];
          foreach ($queuesToResume as $queueToResume) {
            $this->sendingQueuesRepository->resume($queueToResume);
          }
        }
        $this->triggerSending($newsletter);
        return $this->successResponse();
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function cancelHeld($data = []) {
    $newsletter = $this->newsletterRepository->findOneById(isset($data['newsletter_id']) ? (int)$data['newsletter_id'] : false);
    if (!$newsletter instanceof NewsletterEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
    if (!$this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter)) {
      return $this->errorResponse([
        APIError::BAD_REQUEST => __('This email is not held.', 'mailpoet'),
      ], [], Response::STATUS_BAD_REQUEST);
    }
    $reschedule = !empty($data['reschedule']);
    $isStandard = $newsletter->getType() === NewsletterEntity::TYPE_STANDARD;
    if ($reschedule && !$isStandard) {
      return $this->errorResponse([
        APIError::BAD_REQUEST => __('Only a standard email can be rescheduled.', 'mailpoet'),
      ], [], Response::STATUS_BAD_REQUEST);
    }

    if ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $this->newsletterDeleteController->bulkDelete([(int)$newsletter->getId()]);
      return $this->successResponse();
    }

    $this->deleteHeldCampaignQueues($newsletter);
    if ($isStandard) {
      $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
      if ($reschedule) {
        // Keeps isScheduled, so the send page opens in schedule mode, but without the date that has passed.
        $staleOptions = [
          NewsletterOptionFieldEntity::NAME_SCHEDULED_AT,
          NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE,
          NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME,
        ];
        foreach ($staleOptions as $optionName) {
          $option = $newsletter->getOption($optionName);
          if ($option) {
            $newsletter->getOptions()->removeElement($option);
            $this->newsletterOptionsRepository->remove($option);
          }
        }
      }
      $this->newsletterRepository->flush();
    }
    return $this->successResponse();
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

  private function deleteHeldCampaignQueues(NewsletterEntity $newsletter): void {
    $heldTasks = $this->scheduledTasksRepository->findHeldOverdueForNewsletter($newsletter);
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

  private function syncSenderFromParent(NewsletterEntity $newsletter): void {
    $parent = $newsletter->getParent();
    if (!$parent instanceof NewsletterEntity || !$this->hasParentSender($newsletter)) {
      return;
    }
    $newsletter->setSenderAddress($parent->getSenderAddress());
    $newsletter->setSenderName($parent->getSenderName());
    $this->newsletterRepository->flush();
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
