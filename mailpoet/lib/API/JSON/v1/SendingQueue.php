<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\DaemonActionSchedulerRunner;
use MailPoet\Cron\Triggers\WordPress;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\NewsletterValidator;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

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

  /** @var Scheduler */
  private $scheduler;

  /** @var SettingsController */
  private $settings;

  /** @var DaemonActionSchedulerRunner */
  private $actionSchedulerRunner;

  public function __construct(
    SubscribersFeature $subscribersFeature,
    NewslettersRepository $newsletterRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersFinder $subscribersFinder,
    ScheduledTasksRepository $scheduledTasksRepository,
    MailerFactory $mailerFactory,
    Scheduler $scheduler,
    SettingsController $settings,
    DaemonActionSchedulerRunner $actionSchedulerRunner,
    NewsletterValidator $newsletterValidator
  ) {
    $this->subscribersFeature = $subscribersFeature;
    $this->subscribersFinder = $subscribersFinder;
    $this->newsletterRepository = $newsletterRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->mailerFactory = $mailerFactory;
    $this->scheduler = $scheduler;
    $this->settings = $settings;
    $this->actionSchedulerRunner = $actionSchedulerRunner;
    $this->newsletterValidator = $newsletterValidator;
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
    $newsletter = Newsletter::findOneWithOptions($newsletterId);

    if (!$newsletter instanceof Newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
    $newsletterEntity = $this->newsletterRepository->findOneById($newsletterId);
    if (!$newsletterEntity instanceof NewsletterEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }

    $validationError = $this->newsletterValidator->validate($newsletterEntity);
    if ($validationError) {
      return $this->errorResponse([
        APIError::BAD_REQUEST => $validationError,
      ]);
    }

    // check that the sending method has been configured properly by verifying that default mailer can be build
    try {
      $this->mailerFactory->getDefaultMailer();
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }

    // add newsletter to the sending queue
    $queue = SendingQueueModel::joinWithTasks()
      ->where('queues.newsletter_id', $newsletterEntity->getId())
      ->whereNull('tasks.status')
      ->findOne();

    if (!empty($queue)) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter is already being sent.', 'mailpoet'),
      ]);
    }

    $scheduledQueue = SendingQueueModel::joinWithTasks()
      ->where('queues.newsletter_id', $newsletterEntity->getId())
      ->where('tasks.status', SendingQueueModel::STATUS_SCHEDULED)
      ->findOne();
    if ($scheduledQueue instanceof SendingQueueModel) {
      $queue = SendingTask::createFromQueue($scheduledQueue);
    } else {
      $queue = SendingTask::create();
      $queue->newsletterId = $newsletterEntity->getId();
    }

    WordPress::resetRunInterval();
    if ((bool)$newsletterEntity->getOptionValue('isScheduled')) {
      // set newsletter status
      $newsletterEntity->setStatus(NewsletterEntity::STATUS_SCHEDULED);

      // set queue status
      $queue->status = SendingQueueModel::STATUS_SCHEDULED;
      $queue->scheduledAt = $this->scheduler->formatDatetimeString($newsletterEntity->getOptionValue('scheduledAt'));
    } else {
      $segments = $newsletterEntity->getSegmentIds();
      $taskModel = $queue->task();
      $taskEntity = $this->scheduledTasksRepository->findOneById($taskModel->id);

      if ($taskEntity instanceof ScheduledTaskEntity) {
        $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments($taskEntity, $segments);
      }

      if (!isset($subscribersCount) || !$subscribersCount) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('There are no subscribers in that list!', 'mailpoet'),
        ]);
      }
      $queue->updateCount();
      $queue->status = null;
      $queue->scheduledAt = null;

      // set newsletter status
      $newsletterEntity->setStatus(Newsletter::STATUS_SENDING);
    }
    $queue->save();
    $this->newsletterRepository->flush();

    $errors = $queue->getErrors();
    if (!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      $this->triggerSending($newsletterEntity);
      return $this->successResponse(
        $newsletter->getQueue()->asArray()
      );
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
        $this->sendingQueuesRepository->pause($queue);
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
      $queue = $newsletter->getLastUpdatedQueue();

      if (!$queue instanceof SendingQueueEntity) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('This newsletter has not been sent yet.', 'mailpoet'),
        ]);
      } else {
        $this->sendingQueuesRepository->resume($queue);
        $this->triggerSending($newsletter);
        return $this->successResponse();
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This newsletter does not exist.', 'mailpoet'),
      ]);
    }
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
      $this->actionSchedulerRunner->trigger();
    }
  }
}
