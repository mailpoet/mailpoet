<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\Models\NewsletterLink;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Security;

class Links {
  const DATA_TAG = '[mailpoet_data]';

  static function extract($content) {
    $extracted_links = array();
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
    $shortcodes = $shortcodes->extract($content, $categories = array('link'));
    $extracted_links = array_map(function ($shortcode) {
      return array(
        'html' => $shortcode,
        'link' => $shortcode
      );
    }, $shortcodes);
    // extract urls with href="url" format
    preg_match_all($regex, $content, $matched_urls);
    for($index = 0; $index <= count($matched_urls[1]); $index++) {
      $extracted_links[] = array(
        'html' => $matched_urls[0][$index],
        'link' => $matched_urls[2][$index]
      );
    }
    return $extracted_links;
  }

  static function process($content) {
    $extracted_links = self::extract($content);
    $processed_links = array();
    foreach($extracted_links as $extracted_link) {
      $hash = Security::generateRandomString(5);
      $processed_links[] = array(
        'hash' => $hash,
        'url' => $extracted_link['link']
      );
      $params = array(
        'mailpoet' => '',
        'endpoint' => 'track',
        'action' => 'click',
        'data' => self::DATA_TAG . '-' . $hash
      );
      $tracked_link = add_query_arg($params, home_url());
      // first, replace URL in the extracted HTML source with encoded link
      $tracked_link_html_source = str_replace(
        $extracted_link['link'], $tracked_link,
        $extracted_link['html']
      );
      // second, replace original extracted HTML source with tracked URL source
      $content = str_replace(
        $extracted_link['html'], $tracked_link_html_source, $content
      );
      // third, replace text version URL with tracked link: [description](url)
      // regex is used to avoid replacing description URLs that are wrapped in round brackets
      // i.e., <a href="http://google.com">(http://google.com)</a> => [(http://google.com)](http://tracked_link)
      $regex_escaped_tracked_link = preg_quote($tracked_link, '/');
      $content = preg_replace(
        '/(\[' . $regex_escaped_tracked_link . '\])(\(' . $regex_escaped_tracked_link . '\))/',
        '[$1](' . $tracked_link . ')',
        $content
      );
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