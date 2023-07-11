<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Subscribers\EngagementDataBackfiller;
use MailPoet\WP\Functions as WPFunctions;

class BackfillEngagementData extends SimpleWorker {
  const TASK_TYPE = 'backfill_engagement_data';
  const BATCH_SIZE = 100;
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var EngagementDataBackfiller */
  private $engagementDataBackfiller;

  public function __construct(
    EngagementDataBackfiller $engagementDataBackfiller,
    WPFunctions $wp
  ) {
    parent::__construct($wp);
    $this->engagementDataBackfiller = $engagementDataBackfiller;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $meta = $task->getMeta();

    $lastSubscriberId = $meta['last_subscriber_id'] ?? 0;

    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $batch = $this->engagementDataBackfiller->getBatch($lastSubscriberId);
      if (empty($batch)) {
        $this->engagementDataBackfiller->setLastProcessedSubscriberId($lastSubscriberId);
        break;
      }
      $this->engagementDataBackfiller->updateBatch($batch);
      $lastSubscriberId = $this->engagementDataBackfiller->getLastProcessedSubscriberId();
    } while (count($batch) === self::BATCH_SIZE);

    $meta['last_subscriber_id'] = $this->engagementDataBackfiller->getLastProcessedSubscriberId();
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return true;
  }
}
