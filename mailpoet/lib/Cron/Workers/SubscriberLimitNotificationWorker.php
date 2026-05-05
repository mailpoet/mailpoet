<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Subscribers\SubscriberLimitNotificationEvaluator;

class SubscriberLimitNotificationWorker extends SimpleWorker {
  const TASK_TYPE = 'subscriber_limit_notification';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var SubscriberLimitNotificationEvaluator */
  private $evaluator;

  public function __construct(
    SubscriberLimitNotificationEvaluator $evaluator
  ) {
    parent::__construct();
    $this->evaluator = $evaluator;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $this->evaluator->evaluate();
    return true;
  }
}
