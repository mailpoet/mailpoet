<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Newsletter\Shortcodes\Shortcodes as NewsletterShortcodes;

if(!defined('ABSPATH')) exit;

class Shortcodes {
  static function process($content, $newsletter, $subscriber, $queue) {
    $shortcodes = new NewsletterShortcodes($newsletter, $subscriber, $queue);
    return $shortcodes->replace($content);
  }
}