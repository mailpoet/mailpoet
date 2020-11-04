<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class Date implements CategoryInterface {
  public function process(
    array $shortcodeDetails,
    NewsletterEntity $newsletter = null,
    SubscriberEntity $subscriber = null,
    SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    $actionMapping = [
      'd' => 'd',
      'dordinal' => 'jS',
      'dtext' => 'l',
      'm' => 'm',
      'mtext' => 'F',
      'y' => 'Y',
    ];
    $wp = new WPFunctions();
    if (!empty($actionMapping[$shortcodeDetails['action']])) {
      return WPFunctions::get()->dateI18n($actionMapping[$shortcodeDetails['action']], $wp->currentTime('timestamp'));
    }
    return ($shortcodeDetails['action'] === 'custom' && $shortcodeDetails['action_argument'] === 'format') ?
      WPFunctions::get()->dateI18n($shortcodeDetails['action_argument_value'], $wp->currentTime('timestamp')) :
      null;
  }
}
