<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsUnsubscribes;

if (!defined('ABSPATH')) exit;

class Unsubscribes {
  function track($newsletter_id, $subscriber_id, $queue_id) {
    $statistics = StatisticsUnsubscribes::where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if (!$statistics) {
      $statistics = StatisticsUnsubscribes::create();
      $statistics->newsletter_id = $newsletter_id;
      $statistics->subscriber_id = $subscriber_id;
      $statistics->queue_id = $queue_id;
      $statistics->save();
    }
  }
}
