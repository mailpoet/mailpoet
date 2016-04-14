<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\Util\Security;

class Links {
  public $newsletter_id;
  public $queue_id;
  public $subscriber_id;

  function __construct(
    $newsletter_id = false,
    $subscriber_id = false,
    $queue_id = false) {
    $this->newsletter_id = $newsletter_id;
    $this->queue_id = $queue_id;
    $this->subscriber_id = $subscriber_id;
  }

  function extract($text) {
    // adopted from WP's wp_extract_urls() function &  modified to work on hrefs
    $regex = '#(?:href.*?=.*?)(["\']?)('
      . '(?:([\w-]+:)?//?)'
      . '[^\s()<>]+'
      . '[.]'
      . '(?:'
      . '\([\w\d]+\)|'
      . '(?:'
      . '[^`!()\[\]{};:\'".,<>«»“”‘’\s]|'
      . '(?:[:]\d+)?/?'
      . ')+'
      . ')'
      . ')\\1#';
    preg_match_all($regex, $text, $links);
    preg_match_all('/\[\w+:\w+\]/', $text, $shortcodes);
    return array_merge(
      array_unique($links[2]),
      array_unique($shortcodes[0])
    );
  }

  function replace($text, $links = false) {
    $links = ($links) ? $links : $this->extract($text);
    $processed_links = array();
    foreach($links as $link) {
      $hash = Security::generateRandomString(5);
      $processed_links[] = array(
        'hash' => $hash,
        'url' => $link
      );
      $encoded_link = sprintf(
        '%s/?mailpoet&endpoint=track&data=%s',
        home_url(),
        '[mailpoet_data]-'.$hash
      );
      $link_regex = '/' . preg_quote($link, '/') . '/';
      $text = preg_replace($link_regex, $encoded_link, $text);
    }
    return array($text, $processed_links);
  }
}