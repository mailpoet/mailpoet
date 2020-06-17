<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
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

  public function track(int $subscriberId, int $queueId = null, string $source) {
    if ($queueId) {
      $queue = $this->sendingQueuesRepository->findOneById($queueId);
    }
    if ($queue instanceof SendingQueueEntity) {
      $newsletter = $queue->getNewsletter();
      if ($newsletter instanceof NewsletterEntity) {
        $statistics = $this->statisticsUnsubscribesRepository->findOneBy(
          [
            'queue' => $queue,
            'newsletter' => $newsletter,
            'subscriberId' => $subscriberId,
          ]
        );
        if (!$statistics) {
          $statistics = new StatisticsUnsubscribeEntity($newsletter, $queue, $subscriberId);
        }
      }
    }

    if (!$statistics) {
      $statistics = new StatisticsUnsubscribeEntity(null, null, $subscriberId);
    }
    $statistics->setSource($source);
    $this->statisticsUnsubscribesRepository->persist($statistics);
    $this->statisticsUnsubscribesRepository->flush();
  }
}
