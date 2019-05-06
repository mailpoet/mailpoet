<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\StatisticsClicks as StatisticsClicksModel;
use MailPoet\Models\Subscriber;

class StatisticsClicks {
  protected $data;

  public function __construct(NewsletterLink $newsletter_link, Subscriber $subscriber) {
    $this->data = [
      'newsletter_id' => $newsletter_link->newsletter_id,
      'subscriber_id' => $subscriber->id,
      'queue_id' => $newsletter_link->queue_id,
      'link_id' => $newsletter_link->id,
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
