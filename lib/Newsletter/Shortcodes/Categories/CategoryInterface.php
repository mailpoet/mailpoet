<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

interface CategoryInterface {
  public function process(
    array $shortcodeDetails,
    NewsletterEntity $newsletter = null,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string;
}
