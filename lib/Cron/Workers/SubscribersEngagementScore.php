<?php declare(strict_types=1);

namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersEngagementScore extends SimpleWorker {
  const AUTOMATIC_SCHEDULING = false;
  const BATCH_SIZE = 60;
  const TASK_TYPE = 'subscribers_engagement_score';

  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    StatisticsOpensRepository $statisticsOpensRepository,
    SubscribersRepository $subscribersRepository
  ) {
    parent::__construct();
    $this->statisticsOpensRepository = $statisticsOpensRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {

  }

  public function getNextRunDate() {
    return Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
  }
}
