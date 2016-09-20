<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Links {
  static function process($rendered_newsletter, $newsletter, $queue) {
    list($rendered_newsletter, $links) =
      self::hashAndReplaceLinks($rendered_newsletter);
    self::saveLinks($links, $newsletter, $queue);
    return $rendered_newsletter;
  }

  static function hashAndReplaceLinks($rendered_newsletter) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($rendered_newsletter);
    list($content, $links) = NewsletterLinks::process($content);
    // split the processed body with hashed links back to HTML and TEXT
    list($rendered_newsletter['html'], $rendered_newsletter['text'])
      = Helpers::splitObject($content);
    return array(
      $rendered_newsletter,
      $links
    );
  }

  static function saveLinks($links, $newsletter, $queue) {
    return NewsletterLinks::save($links, $newsletter->id, $queue->id);
  }
}