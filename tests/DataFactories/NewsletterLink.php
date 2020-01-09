<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink as NewsletterLinkModel;

class NewsletterLink {
  protected $data;

  public function __construct(Newsletter $newsletter) {
    $this->data = [
      'newsletter_id' => $newsletter->id,
      'queue_id' => $newsletter->getQueue()->id,
      'url' => 'https://example.com/test',
      'hash' => 'hash',
    ];
  }

  public function withUrl($url) {
    $this->data['url'] = $url;
    return $this;
  }

  public function withHash($hash) {
    $this->data['hash'] = $hash;
    return $this;
  }

  /**
   * @param string $created_at in format Y-m-d H:i:s
   * @return NewsletterLink
   */
  public function withCreatedAt($createdAt) {
    $this->data['created_at'] = $createdAt;
    return $this;
  }

  /** @return NewsletterLinkModel */
  public function create() {
    return NewsletterLinkModel::createOrUpdate($this->data);
  }
}
