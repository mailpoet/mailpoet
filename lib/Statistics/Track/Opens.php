<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsOpens;

if(!defined('ABSPATH')) exit;

class Opens {
  static function track($data, $display_image = true) {
    if(!$data) return self::returnResponse($display_image);
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if(!$data->preview && !$subscriber->isWPUser()) {
      StatisticsOpens::getOrCreate(
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
    }
    return self::returnResponse($display_image);
  }

  static function returnResponse($display_image) {
    if(!$display_image) return;
    // return 1x1 pixel transparent gif image
    header('Content-Type: image/gif');
    echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    exit;
  }
}