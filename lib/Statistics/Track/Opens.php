<?php

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\StatisticsOpens;

class Opens {
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
    $wpUserPreview = ($data->preview && ($subscriber->getWpUserId() > 0));
    // log statistics only if the action did not come from
    // a WP user previewing the newsletter
    if (!$wpUserPreview) {
      StatisticsOpens::getOrCreate(
        $subscriber->getId(),
        $newsletter->getId(),
        $queue->getId()
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
