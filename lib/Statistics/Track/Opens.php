<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsOpensRepository;

class Opens {
  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  public function __construct(StatisticsOpensRepository $statisticsOpensRepository) {
    $this->statisticsOpensRepository = $statisticsOpensRepository;
  }

  public function track($data, $displayImage = true) {
    if (!$data) {
      return $this->returnResponse($displayImage);
    }
    /** @var SubscriberEntity $subscriber */
    $subscriber = $data->subscriber;
    /** @var SendingQueueEntity $queue */
    $queue = $data->queue;
    /** @var NewsletterEntity $newsletter */
    $newsletter = $data->newsletter;
    $wpUserPreview = ($data->preview && ($subscriber->isWPUser()));
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      $oldStatistics = $this->statisticsOpensRepository->findOneBy([
        'subscriber' => $subscriber->getId(),
        'newsletter' => $newsletter->getId(),
        'queue' => $queue->getId(),
      ]);
      // Open was already tracked
      if ($oldStatistics) {
        return $this->returnResponse($displayImage);
      }
      $statistics = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
      $this->statisticsOpensRepository->persist($statistics);
      $this->statisticsOpensRepository->flush();
      $this->statisticsOpensRepository->recalculateSubscriberScore($subscriber);
    }
    return $this->returnResponse($displayImage);
  }

  public function returnResponse($displayImage) {
    if (!$displayImage) return;
    // return 1x1 pixel transparent gif image
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
    exit;
  }
}
