<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\Models\NewsletterLink;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Security;

class Links {
  const DATA_TAG = '[mailpoet_data]';

  static function extract($content) {
    // adopted from WP's wp_extract_urls() function &  modified to work on hrefs
    # match href=' or href="
    $regex = '#(?:href.*?=.*?)(["\']?)('
      # match http://
      . '(?:([\w-]+:)?//?)'
      # match everything except for special characters # until .
      . '[^\s()<>]+'
      . '[.]'
      # conditionally match everything except for special characters after .
      . '(?:'
      . '\([\w\d]+\)|'
      . '(?:'
      . '[^`!()\[\]{}:;\'".,<>«»“”‘’\s]|'
      . '(?:[:]\d+)?/?'
      . ')+'
      . ')'
      . ')\\1#';
    // extract shortcodes with [link:*] format
    $shortcodes = new Shortcodes();
    $shortcodes = $shortcodes->extract($content, $limit = array('link'));
    // extract links
    preg_match_all($regex, $content, $links);
    return array_merge(
      array_unique($links[2]),
      $shortcodes
    );
  }

  static function process($content,
    $links = false,
    $process_link_shortcodes = false,
    $queue = false
  ) {
    if($process_link_shortcodes) {
      $shortcodes = new Shortcodes($newsletter = false, $subscriber = false, $queue);
      // process shortcodes with [link:*] format
      $content = $shortcodes->replace($content, $limit = array('link'));
    }
    $links = ($links) ? $links : self::extract($content);
    $processed_links = array();
    foreach($links as $link) {
      $hash = Security::generateRandomString(5);
      $processed_links[] = array(
        'hash' => $hash,
        'url' => $link
      );
      $encoded_link = sprintf(
        '%s/?mailpoet&endpoint=track&action=click&data=%s-%s',
        home_url(),
        self::DATA_TAG,
        $hash
      );
      $link_regex = '/' . preg_quote($link, '/') . '/';
      $content = preg_replace($link_regex, $encoded_link, $content);
    }
    return array(
      $content,
      $processed_links
    );
  }

  static function replaceSubscriberData(
    $newsletter_id,
    $subscriber_id,
    $queue_id,
    $content
  ) {
    $regex = sprintf('/data=(%s(?:-\w+)?)/', preg_quote(self::DATA_TAG));
    preg_match_all($regex, $content, $links);
    foreach($links[1] as $link) {
      $hash = null;
      if(preg_match('/-/', $link)) {
        list(, $hash) = explode('-', $link);
      }
      $data = array(
        'newsletter' => $newsletter_id,
        'subscriber' => $subscriber_id,
        'queue' => $queue_id,
        'hash' => $hash
      );
      $data = rtrim(base64_encode(serialize($data)), '=');
      $content = str_replace($link, $data, $content);
    }
    return $content;
  }

  static function save($links, $newsletter_id, $queue_id) {
    foreach($links as $link) {
      $newsletter_link = NewsletterLink::create();
      $newsletter_link->newsletter_id = $newsletter_id;
      $newsletter_link->queue_id = $queue_id;
      $newsletter_link->hash = $link['hash'];
      $newsletter_link->url = $link['url'];
      $newsletter_link->save();
    }
  }
}