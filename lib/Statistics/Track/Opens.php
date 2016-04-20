<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Opens {
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function track($data = false) {
    $data = ($data) ? $data : $this->data;
    if(!preg_match('/\d+-\d+-\d+/', $data)) $this->abort();
    list ($newsletter_id, $subscriber_id, $queue_id) = explode('-', $data);
    $subscriber = Subscriber::findOne($subscriber_id);
    if(!$subscriber) return;
    $statistics = StatisticsOpens::where('subscriber_id', $subscriber_id)
      ->where('newsletter_id', $newsletter_id)
      ->where('queue_id', $queue_id)
      ->findOne();
    if(!$statistics) {
      $statistics = StatisticsOpens::create();
      $statistics->newsletter_id = $newsletter_id;
      $statistics->subscriber_id = $subscriber_id;
      $statistics->queue_id = $queue_id;
      $statistics->save();
    }
    header('Content-Type: image/gif');
    // return 1x1 pixel transparent gif image
    echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    exit;
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}