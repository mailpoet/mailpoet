<?php declare(strict_types=1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Cron\Workers\ReEngagementEmailsScheduler;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ReEngagementScheduler {

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->entityManager = $entityManager;
    $this->wp = $wp;
  }

  /**
   * Schedules sending tasks for re-engagement emails
   * @return ScheduledTaskEntity[]
   */
  public function scheduleAll(): array {
    $scheduled = [];
    $emails = $this->newslettersRepository->findActiveByTypes([NewsletterEntity::TYPE_RE_ENGAGEMENT]);
    if (!$emails) {
      return $scheduled;
    }
    foreach ($emails as $email) {
      $scheduled[] = $this->scheduleForEmail($email);
    }
    return array_filter($scheduled);
  }

  private function scheduleForEmail(NewsletterEntity $email): ?ScheduledTaskEntity {
    $scheduledOrRunning = $this->scheduledTasksRepository->findByScheduledAndRunningForNewsletter($email);
    if ($scheduledOrRunning) {
      return null;
    }
    $intervalUnit = $email->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE);
    $intervalValue = (int)$email->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER);
    if (!$intervalValue || !in_array($intervalUnit, ['weeks', 'months'], true)) {
      return null;
    }
    if (!$email->getNewsletterSegments()->count()) {
      return null;
    }

    $thresholdDate = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    if ($intervalUnit === 'months') {
      $thresholdDate->subMonths($intervalValue);
    } else {
      $thresholdDate->subWeeks($intervalValue);
    }
    // Scheduled task
    $scheduledTask = new ScheduledTaskEntity();
    $scheduledTask->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $scheduledTask->setScheduledAt(Carbon::createFromTimestamp($this->wp->currentTime('timestamp')));
    $scheduledTask->setType(ReEngagementEmailsScheduler::TASK_TYPE);
    $this->scheduledTasksRepository->persist($scheduledTask);

    // Sending queue
    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setTask($scheduledTask);
    $sendingQueue->setNewsletter($email);
    $this->scheduledTasksRepository->persist($sendingQueue);
    $this->scheduledTasksRepository->flush();

    // Parameters for scheduled task subscribers query
    $taskId = $scheduledTask->getId();
    $subscribedStatus = SubscriberEntity::STATUS_SUBSCRIBED;
    $thresholdDateSql = $thresholdDate->toDateTimeString();
    $newsletterId = $email->getId();
    $segmentIds = $email->getSegmentIds();
    $newsletterStatsTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $scheduledTaskSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $nowSql = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->toDateTimeString();

    $query = "INSERT IGNORE INTO $scheduledTaskSubscribersTable
        (subscriber_id, task_id,  processed, created_at)
        SELECT DISTINCT ns.subscriber_id as subscriber_id, :taskId as task_id, 0 as processed, :now as created_at FROM $newsletterStatsTable as ns
        JOIN $subscribersTable s ON ns.subscriber_id = s.id AND s.deleted_at is NULL AND s.status = :subscribed AND GREATEST(COALESCE(s.created_at, '0'), COALESCE(s.last_subscribed_at, '0'), COALESCE(s.last_engagement_at, '0')) < :thresholdDate
        JOIN wp_mailpoet_subscriber_segment as ss ON ns.subscriber_id = ss.subscriber_id AND ss.segment_id IN (" . implode(',', $segmentIds) . ") AND ss.status = :subscribed
      WHERE ns.sent_at > :thresholdDate AND ns.subscriber_id NOT IN (SELECT DISTINCT subscriber_id as id FROM $newsletterStatsTable WHERE newsletter_id = :newsletterId AND sent_at > :thresholdDate);
    ";

    $statement = $this->entityManager->getConnection()->prepare($query);
    $statement->bindParam('now', $nowSql, ParameterType::STRING);
    $statement->bindParam('taskId', $taskId, ParameterType::INTEGER);
    $statement->bindParam('subscribed', $subscribedStatus, ParameterType::STRING);
    $statement->bindParam('thresholdDate', $thresholdDateSql, ParameterType::STRING);
    $statement->bindParam('newsletterId', $newsletterId, ParameterType::INTEGER);

    $statement->executeQuery();
    return $scheduledTask;
  }
}
