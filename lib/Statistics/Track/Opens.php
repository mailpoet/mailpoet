<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Opens {
  public $data;
  public $display_image;

  function __construct($data, $display_image = true) {
    $this->data = $data;
    $this->display_image = $display_image;
  }

  function track($data = false) {
    $data = ($data) ? $data : $this->data;
    $subscriber = Subscriber::findOne($data['subscriber']);
    if(!$subscriber) return;
    $statistics = StatisticsOpens::where('subscriber_id', $subscriber->id)
      ->where('newsletter_id', $data['newsletter'])
      ->where('queue_id', $data['queue'])
      ->findOne();
    if(!$statistics) {
      $statistics = StatisticsOpens::create();
      $statistics->newsletter_id = $data['newsletter'];
      $statistics->subscriber_id = $data['subscriber'];
      $statistics->queue_id = $data['queue'];
      $statistics->save();
    }
    if($this->display_image) {
      // return 1x1 pixel transparent gif image
      header('Content-Type: image/gif');
      echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
      exit;
    }
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}