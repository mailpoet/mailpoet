<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class Site implements CategoryInterface {
  public function process(
    array $shortcodeDetails,
    NewsletterEntity $newsletter = null,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    $wp = new WPFunctions();

    switch ($shortcodeDetails['action']) {
      case 'title':
        return $wp->getBloginfo('name');

      case 'homepage_link':
        return $wp->getBloginfo('url');

      default:
        return null;
    }
  }
}
