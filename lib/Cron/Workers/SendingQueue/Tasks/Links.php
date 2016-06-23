<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Links {
  static function process(array $newsletter, array $queue) {
    list($newsletter, $links) = self::hashAndReplaceLinks($newsletter, $queue);
    self::saveLinks($links, $newsletter, $queue);
    return $newsletter;
  }

  static function hashAndReplaceLinks(array $newsletter, array $queue) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($newsletter['rendered_body']);
    list($content, $links) = NewsletterLinks::process($content);
    // split the processed body with hashed links back to HTML and TEXT
    list($newsletter['rendered_body']['html'], $newsletter['rendered_body']['text'])
      = Helpers::splitObject($content);
    return array(
      $newsletter,
      $links
    );
  }

  static function saveLinks($links, $newsletter, $queue) {
    return NewsletterLinks::save($links, $newsletter['id'], $queue['id']);
  }
}

