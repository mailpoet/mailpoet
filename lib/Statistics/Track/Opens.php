<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Models\StatisticsOpens;

class Opens {
  public function track($data, $displayImage = true) {
    if (!$data) {
      return $this->returnResponse($displayImage);
    }
    $subscriber = $data->subscriber;
    $queue = $data->queue;
    $newsletter = $data->newsletter;
    $wpUserPreview = ($data->preview && $subscriber->isWPUser());
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      StatisticsOpens::getOrCreate(
        $subscriber->id,
        $newsletter->id,
        $queue->id
      );
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
