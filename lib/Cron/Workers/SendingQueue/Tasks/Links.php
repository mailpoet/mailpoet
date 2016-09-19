<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Links {
  static function process($newsletter, $queue) {
    list($rendered_body, $links) =
      self::hashAndReplaceLinks($newsletter->_transient->rendered_body);
    self::saveLinks($links, $newsletter, $queue);
    $newsletter->_transient->rendered_body = $rendered_body;
    return $newsletter;
  }

  static function hashAndReplaceLinks($newsletter_rendered_body) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($newsletter_rendered_body);
    list($content, $links) = NewsletterLinks::process($content);
    // split the processed body with hashed links back to HTML and TEXT
    list($newsletter_rendered_body['html'], $newsletter_rendered_body['text'])
      = Helpers::splitObject($content);
    return array(
      $newsletter_rendered_body,
      $links
    );
  }

  static function saveLinks($links, $newsletter, $queue) {
    return NewsletterLinks::save($links, $newsletter->id, $queue->id);
  }
}