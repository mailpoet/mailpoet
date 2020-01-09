<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\StatisticsClicks as StatisticsClicksModel;
use MailPoet\Models\Subscriber;

class StatisticsClicks {
  protected $data;

  public function __construct(NewsletterLink $newsletterLink, Subscriber $subscriber) {
    $this->data = [
      'newsletter_id' => $newsletterLink->newsletterId,
      'subscriber_id' => $subscriber->id,
      'queue_id' => $newsletterLink->queueId,
      'link_id' => $newsletterLink->id,
      'count' => 1,
    ];
  }

  public function withCount($count) {
    $this->data['count'] = $count;
    return $this;
  }

  /** @return StatisticsClicksModel */
  public function create() {
    return StatisticsClicksModel::createOrUpdate($this->data);
  }
}
