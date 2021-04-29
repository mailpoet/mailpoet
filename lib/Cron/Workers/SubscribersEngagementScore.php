<?php declare(strict_types=1);

namespace MailPoet\Cron\Workers;

use MailPoet\Models\ScheduledTask;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Subscribers\SubscribersRepository;
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
    $subscribers = $this->subscribersRepository->findByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    foreach ($subscribers as $subscriber) {
      $this->statisticsOpensRepository->recalculateSubscriberScore($subscriber);
    }
    if ($subscribers) {
      $this->scheduleImmediately();
    } else {
      $this->schedule();
    }
    return true;
  }

  public function getNextRunDate() {
    // random day of the next week
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $date->addDay();
    $date->setTime(mt_rand(0, 23), mt_rand(0, 59));
    return $date;
  }
}
