<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\API\API;
use MailPoet\API\Endpoints\Track as TrackAPI;
use MailPoet\Models\NewsletterLink;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Util\Security;

class Links {
  const DATA_TAG_CLICK = '[mailpoet_click_data]';
  const DATA_TAG_OPEN = '[mailpoet_open_data]';
  const HASH_LENGTH = 5;

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
    $matched_urls_count = count($matched_urls[0]);
    if($matched_urls_count) {
      for($index = 0; $index < $matched_urls_count; $index++) {
        $extracted_links[] = array(
          'html' => $matched_urls[0][$index],
          'link' => $matched_urls[2][$index]
        );
      }
    }
    return array_unique($extracted_links, SORT_REGULAR);
  }

  static function process($content) {
    $extracted_links = self::extract($content);
    $processed_links = array();
    foreach($extracted_links as $extracted_link) {
      $hash = Security::generateRandomString(self::HASH_LENGTH);
      $processed_links[] = array(
        'hash' => $hash,
        'url' => $extracted_link['link']
      );
      // replace link with a temporary data tag + hash
      // it will be further replaced with the proper track API URL during sending
      $tracked_link = self::DATA_TAG_CLICK . '-' . $hash;
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
      $regex_escaped_extracted_link = preg_quote($extracted_link['link'], '/');
      $content = preg_replace(
        '/\[(' . $regex_escaped_extracted_link . ')\](\(' . $regex_escaped_extracted_link . '\))/',
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
    // match data tags
    $regex = sprintf(
      '/((%s|%s)(?:-\w+)?)/',
      preg_quote(self::DATA_TAG_CLICK),
      preg_quote(self::DATA_TAG_OPEN)
    );
    preg_match_all($regex, $content, $matches);
    foreach($matches[1] as $index => $match) {
      $hash = null;
      if(preg_match('/-/', $match)) {
        list(, $hash) = explode('-', $match);
      }
      $data = array(
        'newsletter' => $newsletter_id,
        'subscriber' => $subscriber_id,
        'queue' => $queue_id,
        'hash' => $hash
      );
      $API_action = ($matches[2][$index] === self::DATA_TAG_CLICK) ?
        TrackAPI::ACTION_CLICK :
        TrackAPI::ACTION_OPEN;
      $link = API::buildRequest(
        TrackAPI::ENDPOINT,
        $API_action,
        $data
      );
      $content = str_replace($match, $link, $content);
    }
    return $content;
  }

  static function save(array $links, $newsletter_id, $queue_id) {
    foreach($links as $link) {
      if(empty($link['hash'] || empty($link['url']))) continue;
      $newsletter_link = NewsletterLink::create();
      $newsletter_link->newsletter_id = $newsletter_id;
      $newsletter_link->queue_id = $queue_id;
      $newsletter_link->hash = $link['hash'];
      $newsletter_link->url = $link['url'];
      $newsletter_link->save();
    }
  }
}