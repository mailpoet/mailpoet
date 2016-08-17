<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsOpens;

if(!defined('ABSPATH')) exit;

class Opens {
  static function track($data, $display_image = true) {
    if(!$data) return self::displayImage();
    $subscriber = $data['subscriber'];
    $queue = $data['queue'];
    $newsletter = $data['newsletter'];
    // log statistics only if the action did not come from
    // an admin user previewing the newsletter
    if (!$data['preview'] && !$subscriber['wp_user_id']) {
      $statistics = StatisticsOpens::where('subscriber_id', $subscriber['id'])
        ->where('newsletter_id', $newsletter['id'])
        ->where('queue_id', $queue['id'])
        ->findOne();
      if(!$statistics) {
        $statistics = StatisticsOpens::create();
        $statistics->newsletter_id = $newsletter['id'];
        $statistics->subscriber_id = $subscriber['id'];
        $statistics->queue_id = $queue['id'];
        $statistics->save();
      }
    }
    return ($display_image) ?
      self::displayImage() :
      true;
  }

  static function displayImage() {
    // return 1x1 pixel transparent gif image
    header('Content-Type: image/gif');
    echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    exit;
  }
}