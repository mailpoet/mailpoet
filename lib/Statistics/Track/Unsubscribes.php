<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;

class Unsubscribes {
  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  public function __construct(
    SendingQueuesRepository $sendingQueuesRepository,
    StatisticsUnsubscribesRepository $statisticsUnsubscribesRepository
  ) {
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->statisticsUnsubscribesRepository = $statisticsUnsubscribesRepository;
  }

  public function track(int $subscriberId, int $queueId) {
    $queue = $this->sendingQueuesRepository->findOneById($queueId);
    if ($queue === null) {
      return;
    }
    $newsletter = $queue->getNewsletter();
    if ($newsletter === null) {
      return;
    }
    $statistics = $this->statisticsUnsubscribesRepository->findOneBy([
      'queue' => $queue,
      'newsletter' => $newsletter,
      'subscriberId' => $subscriberId,
    ]);

    if (!$statistics) {
      $statistics = new StatisticsUnsubscribeEntity($newsletter, $queue, $subscriberId);
      $this->statisticsUnsubscribesRepository->persist($statistics);
      $this->statisticsUnsubscribesRepository->flush();
    }
  }
}
