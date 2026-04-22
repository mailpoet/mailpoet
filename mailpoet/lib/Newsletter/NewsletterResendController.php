<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Cron\ActionScheduler\Actions\DaemonTrigger;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class NewsletterResendController {
  const MIN_RESEND_DELAY_HOURS = 24;
  const MAX_RESEND_DELAY_HOURS = 72;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  /** @var NewsletterDeleteController */
  private $newsletterDeleteController;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var DaemonTrigger */
  private $daemonTrigger;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var MailerFactory */
  private $mailerFactory;

  public function __construct(
    NewsletterSaveController $newsletterSaveController,
    NewsletterDeleteController $newsletterDeleteController,
    NewslettersRepository $newslettersRepository,
    EntityManager $entityManager,
    ScheduledTasksRepository $scheduledTasksRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    WPFunctions $wp,
    DaemonTrigger $daemonTrigger,
    SettingsController $settings,
    SubscribersFeature $subscribersFeature,
    MailerFactory $mailerFactory
  ) {
    $this->newsletterSaveController = $newsletterSaveController;
    $this->newsletterDeleteController = $newsletterDeleteController;
    $this->newslettersRepository = $newslettersRepository;
    $this->entityManager = $entityManager;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->wp = $wp;
    $this->daemonTrigger = $daemonTrigger;
    $this->settings = $settings;
    $this->subscribersFeature = $subscribersFeature;
    $this->mailerFactory = $mailerFactory;
  }

  /**
   * Extensibility hook for premium/plan restrictions.
   * Override or decorate this method to add per-site daily caps,
   * plan-based limits, or other restrictions.
   */
  public function canResend(NewsletterEntity $newsletter): void {
    if ($newsletter->getType() !== NewsletterEntity::TYPE_STANDARD) {
      throw UnexpectedValueException::create()
        ->withMessage(__('Only standard newsletters can be resent.', 'mailpoet'));
    }

    if ($newsletter->getStatus() !== NewsletterEntity::STATUS_SENT) {
      throw UnexpectedValueException::create()
        ->withMessage(__('Only sent newsletters can be resent.', 'mailpoet'));
    }

    $queue = $newsletter->getLatestQueue();
    if ($queue && $queue->getCountToProcess() > 0) {
      throw UnexpectedValueException::create()
        ->withMessage(__('This email is still being sent. Wait until sending is complete.', 'mailpoet'));
    }

    if ($newsletter->getParent() !== null) {
      throw UnexpectedValueException::create()
        ->withMessage(__('A resent email cannot be resent again.', 'mailpoet'));
    }

    if (!$newsletter->getChildren()->isEmpty()) {
      throw UnexpectedValueException::create()
        ->withMessage(__('This email has already been resent.', 'mailpoet'));
    }

    $sentAt = $newsletter->getSentAt();
    if (!$sentAt) {
      throw UnexpectedValueException::create()
        ->withMessage(__('This email has no sent date and cannot be resent.', 'mailpoet'));
    }

    $hoursSinceSent = Carbon::now()->diffInHours(Carbon::createFromTimestamp($sentAt->getTimestamp()), true);
    if ($hoursSinceSent < self::MIN_RESEND_DELAY_HOURS) {
      throw UnexpectedValueException::create()
        ->withMessage(__('You can resend this email at least 1 day after it was sent.', 'mailpoet'));
    }
    if ($hoursSinceSent > self::MAX_RESEND_DELAY_HOURS) {
      throw UnexpectedValueException::create()
        ->withMessage(__('You can only resend this email within 3 days of sending it.', 'mailpoet'));
    }

    if ($this->subscribersFeature->check()) {
      throw UnexpectedValueException::create()
        ->withMessage(__('Subscribers limit reached.', 'mailpoet'));
    }

    try {
      $this->mailerFactory->getDefaultMailer();
    } catch (\Exception $e) {
      throw UnexpectedValueException::create()
        ->withMessage($e->getMessage());
    }
  }

  public function resendToNonOpeners(NewsletterEntity $newsletter, string $subject): NewsletterEntity {
    $this->canResend($newsletter);

    $subject = trim($subject);
    if ($subject === '') {
      throw UnexpectedValueException::create()
        ->withMessage(__('Subject line is required.', 'mailpoet'));
    }

    $originalSubject = trim($newsletter->getSubject());
    if (strcasecmp($subject, $originalSubject) === 0) {
      throw UnexpectedValueException::create()
        ->withMessage(__('The subject line must be different from the original email.', 'mailpoet'));
    }

    $nonOpenerIds = $this->getNonOpenerIds($newsletter);
    if (empty($nonOpenerIds)) {
      throw UnexpectedValueException::create()
        ->withMessage(__('All recipients have already opened this email.', 'mailpoet'));
    }

    $duplicate = $this->newsletterSaveController->duplicate($newsletter);
    $duplicate->setParent($newsletter);
    $duplicate->setSubject($subject);

    $wpPostId = $duplicate->getWpPostId();
    if ($wpPostId) {
      $this->wp->wpUpdatePost([
        'ID' => $wpPostId,
        'post_title' => $subject,
      ]);
    }

    $this->newslettersRepository->flush();

    try {
      $scheduledTask = new ScheduledTaskEntity();
      $scheduledTask->setType(SendingQueueWorker::TASK_TYPE);
      $scheduledTask->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
      $scheduledTask->setStatus(null);
      $this->scheduledTasksRepository->persist($scheduledTask);
      $this->scheduledTasksRepository->flush();

      $sendingQueue = new SendingQueueEntity();
      $sendingQueue->setNewsletter($duplicate);
      $sendingQueue->setTask($scheduledTask);
      $this->sendingQueuesRepository->persist($sendingQueue);
      $this->sendingQueuesRepository->flush();
      $this->newslettersRepository->refresh($duplicate);

      $segmentIds = $newsletter->getSegmentIds();
      $filterSegmentId = $newsletter->getFilterSegmentId();
      $insertedCount = $this->addNonOpenersToTask($scheduledTask, $nonOpenerIds, $segmentIds, $filterSegmentId);

      if ($insertedCount === 0) {
        throw UnexpectedValueException::create()
          ->withMessage(__('No eligible recipients found. All non-openers have since unsubscribed, bounced, or left the original segments.', 'mailpoet'));
      }

      $this->sendingQueuesRepository->updateCounts($sendingQueue);
      $duplicate->setStatus(NewsletterEntity::STATUS_SENDING);
      $this->newslettersRepository->flush();
    } catch (\Throwable $e) {
      $this->cleanupDuplicate($duplicate, $scheduledTask ?? null, $sendingQueue ?? null);
      throw $e;
    }

    if ($this->settings->get('cron_trigger.method') === CronTrigger::METHOD_ACTION_SCHEDULER) {
      $this->daemonTrigger->process();
    }

    return $duplicate;
  }

  private function cleanupDuplicate(
    NewsletterEntity $duplicate,
    ?ScheduledTaskEntity $task,
    ?SendingQueueEntity $queue
  ): void {
    if ($queue) {
      $this->entityManager->remove($queue);
    }
    if ($task) {
      $this->entityManager->remove($task);
    }
    $this->entityManager->flush();
    $duplicateId = $duplicate->getId();
    if ($duplicateId) {
      $this->newsletterDeleteController->bulkDelete([$duplicateId]);
    }
  }

  /** @return int[] */
  private function getNonOpenerIds(NewsletterEntity $newsletter): array {
    $statisticsNewsletterTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $statisticsOpenTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();

    $result = $connection->executeQuery(
      "SELECT DISTINCT sn.subscriber_id
       FROM $statisticsNewsletterTable sn
       LEFT JOIN $statisticsOpenTable so
         ON so.newsletter_id = sn.newsletter_id
         AND so.subscriber_id = sn.subscriber_id
       WHERE sn.newsletter_id = ?
         AND so.id IS NULL",
      [
        $newsletter->getId(),
      ],
      [
        ParameterType::INTEGER,
      ]
    );

    return array_map(function ($id) {
      return (int)$id;
    }, $result->fetchFirstColumn());
  }

  /**
   * @param int[] $nonOpenerIds
   * @param int[] $segmentIds
   */
  private function addNonOpenersToTask(ScheduledTaskEntity $task, array $nonOpenerIds, array $segmentIds, ?int $filterSegmentId): int {
    $scheduledTaskSubscriberTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();

    $segmentJoin = '';
    $segmentParams = [];
    $segmentTypes = [];

    if (!empty($segmentIds)) {
      $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();

      $segmentJoin = "INNER JOIN $subscriberSegmentTable ss
         ON ss.subscriber_id = subscribers.id
         AND ss.segment_id IN (?)
         AND ss.status = 'subscribed'";
      $segmentParams[] = $segmentIds;
      $segmentTypes[] = ArrayParameterType::INTEGER;
    }

    $result = $connection->executeQuery(
      "INSERT IGNORE INTO $scheduledTaskSubscriberTable
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM $subscriberTable subscribers
       $segmentJoin
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND subscribers.`id` IN (?)",
      array_merge(
        [
          $task->getId(),
          ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
        ],
        $segmentParams,
        [
          SubscriberEntity::STATUS_SUBSCRIBED,
          $nonOpenerIds,
        ]
      ),
      array_merge(
        [
          ParameterType::INTEGER,
          ParameterType::INTEGER,
        ],
        $segmentTypes,
        [
          ParameterType::STRING,
          ArrayParameterType::INTEGER,
        ]
      )
    );

    return $result->rowCount();
  }
}
