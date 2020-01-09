<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsUnsubscribes;

class Unsubscribes {
  public function track($newsletterId, $subscriberId, $queueId) {
    $statistics = StatisticsUnsubscribes::where('subscriber_id', $subscriberId)
      ->where('newsletter_id', $newsletterId)
      ->where('queue_id', $queueId)
      ->findOne();
    if (!$statistics) {
      $statistics = StatisticsUnsubscribes::create();
      $statistics->newsletterId = $newsletterId;
      $statistics->subscriberId = $subscriberId;
      $statistics->queueId = $queueId;
      $statistics->save();
    }
  }
}
