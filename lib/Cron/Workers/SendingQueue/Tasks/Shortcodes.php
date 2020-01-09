<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Newsletter\Shortcodes\Shortcodes as NewsletterShortcodes;

class Shortcodes {
  public static function process($content, $contentSource = null, $newsletter = null, $subscriber = null, $queue = null) {
    $shortcodes = new NewsletterShortcodes($newsletter, $subscriber, $queue);
    return $shortcodes->replace($content, $contentSource);
  }
}
