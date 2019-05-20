<?php
namespace MailPoet\Newsletter\Links;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Router\Endpoints\Track as TrackEndpoint;
use MailPoet\Router\Router;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\Util\pQuery\pQuery as DomParser;

class Links {
  const DATA_TAG_CLICK = '[mailpoet_click_data]';
  const DATA_TAG_OPEN = '[mailpoet_open_data]';
  const LINK_TYPE_SHORTCODE = 'shortcode';
  const LINK_TYPE_URL = 'link';

  static function process($content, $newsletter_id, $queue_id) {
    $extracted_links = self::extract($content);
    $saved_links = self::load($newsletter_id, $queue_id);
    $processed_links = self::hash($extracted_links, $saved_links);
    return self::replace($content, $processed_links);
  }

  static function extract($content) {
    $extracted_links = [];
    // extract link shortcodes
    $shortcodes = new Shortcodes();
    $shortcodes = $shortcodes->extract(
      $content,
      $categories = [Link::CATEGORY_NAME]
    );
    if ($shortcodes) {
      $extracted_links = array_map(function($shortcode) {
        return [
          'type' => Links::LINK_TYPE_SHORTCODE,
          'link' => $shortcode,
        ];
      }, $shortcodes);
    }
    // extract HTML anchor tags
    $DOM = DomParser::parseStr($content);
    foreach ($DOM->query('a') as $link) {
      if (!$link->href) continue;
      $extracted_links[] = [
        'type' => self::LINK_TYPE_URL,
        'link' => $link->href,
      ];
    }
    return array_unique($extracted_links, SORT_REGULAR);
  }

  static function load($newsletter_id, $queue_id) {
    $links = NewsletterLink::whereEqual('newsletter_id', $newsletter_id)
      ->whereEqual('queue_id', $queue_id)
      ->findMany();
    $saved_links = [];
    foreach ($links as $link) {
      $saved_links[$link->url] = $link->asArray();
    }
    return $saved_links;
  }

  static function hash($extracted_links, $saved_links) {
    $processed_links = array_map(function(&$link) {
      $link['type'] = Links::LINK_TYPE_URL;
      $link['link'] = $link['url'];
      $link['processed_link'] = self::DATA_TAG_CLICK . '-' . $link['hash'];
      return $link;
    }, $saved_links);
    foreach ($extracted_links as $extracted_link) {
      $link = $extracted_link['link'];
      if (array_key_exists($link, $processed_links))
        continue;
      $hash = Security::generateHash();
      // Use URL as a key to map between extracted and processed links
      // regardless of their sequential position (useful for link skips etc.)
      $processed_links[$link] = [
        'type' => $extracted_link['type'],
        'hash' => $hash,
        'link' => $link,
        // replace link with a temporary data tag + hash
        // it will be further replaced with the proper track API URL during sending
        'processed_link' => self::DATA_TAG_CLICK . '-' . $hash,
      ];
    }
    return $processed_links;
  }

  static function replace($content, $processed_links) {
    // replace HTML anchor tags
    $DOM = DomParser::parseStr($content);
    foreach ($DOM->query('a') as $link) {
      $link_to_replace = $link->href;
      $replacement_link = (!empty($processed_links[$link_to_replace]['processed_link'])) ?
        $processed_links[$link_to_replace]['processed_link'] :
        null;
      if (!$replacement_link) continue;
      $link->setAttribute('href', $replacement_link);
    }
    $content = $DOM->__toString();
    // replace link shortcodes and markdown links
    foreach ($processed_links as $processed_link) {
      $link_to_replace = $processed_link['link'];
      $replacement_link = $processed_link['processed_link'];
      if ($processed_link['type'] == self::LINK_TYPE_SHORTCODE) {
        $content = str_replace($link_to_replace, $replacement_link, $content);
      }
      $content = preg_replace(
        '/\[(.*?)\](\(' . preg_quote($link_to_replace, '/') . '\))/',
        '[$1](' . $replacement_link . ')',
        $content
      );
    }
    return [
      $content,
      array_values($processed_links),
    ];
  }

  static function replaceSubscriberData(
    $subscriber_id,
    $queue_id,
    $content,
    $preview = false
  ) {
    // match data tags
    $subscriber = Subscriber::findOne($subscriber_id);
    preg_match_all(self::getLinkRegex(), $content, $matches);
    foreach ($matches[1] as $index => $match) {
      $hash = null;
      if (preg_match('/-/', $match)) {
        list(, $hash) = explode('-', $match);
      }
      $data = self::createUrlDataObject(
        $subscriber->id,
        $subscriber->email,
        $queue_id,
        $hash,
        $preview
      );
      $router_action = ($matches[2][$index] === self::DATA_TAG_CLICK) ?
        TrackEndpoint::ACTION_CLICK :
        TrackEndpoint::ACTION_OPEN;
      $link = Router::buildRequest(
        TrackEndpoint::ENDPOINT,
        $router_action,
        $data
      );
      $content = str_replace($match, $link, $content);
    }
    return $content;
  }

  static function save(array $links, $newsletter_id, $queue_id) {
    foreach ($links as $link) {
      if (isset($link['id']))
        continue;
      if (empty($link['hash']) || empty($link['link'])) continue;
      $newsletter_link = NewsletterLink::create();
      $newsletter_link->newsletter_id = $newsletter_id;
      $newsletter_link->queue_id = $queue_id;
      $newsletter_link->hash = $link['hash'];
      $newsletter_link->url = $link['link'];
      $newsletter_link->save();
    }
  }

  static function convertHashedLinksToShortcodesAndUrls($content, $queue_id, $convert_all = false) {
    preg_match_all(self::getLinkRegex(), $content, $links);
    $links = array_unique(Helpers::flattenArray($links));
    foreach ($links as $link) {
      $link_hash = explode('-', $link);
      if (!isset($link_hash[1])) continue;
      $newsletter_link = NewsletterLink::where('hash', $link_hash[1])
        ->where('queue_id', $queue_id)
        ->findOne();
      // convert either only link shortcodes or all hashes links if "convert all"
      // option is specified
      if (($newsletter_link instanceof NewsletterLink) &&
        (preg_match('/\[link:/', $newsletter_link->url) || $convert_all)
      ) {
        $content = str_replace($link, $newsletter_link->url, $content);
      }
    }
    return $content;
  }

  static function getLinkRegex() {
    return sprintf(
      '/((%s|%s)(?:-\w+)?)/',
      preg_quote(self::DATA_TAG_CLICK),
      preg_quote(self::DATA_TAG_OPEN)
    );
  }

  static function createUrlDataObject(
    $subscriber_id, $subscriber_email, $queue_id, $link_hash, $preview
  ) {
    return [
      $subscriber_id,
      Subscriber::generateToken($subscriber_email),
      $queue_id,
      $link_hash,
      $preview,
    ];
  }

  static function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformed_data = [];
    $transformed_data['subscriber_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformed_data['subscriber_token'] = (!empty($data[1])) ? $data[1] : false;
    $transformed_data['queue_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformed_data['link_hash'] = (!empty($data[3])) ? $data[3] : false;
    $transformed_data['preview'] = (!empty($data[4])) ? $data[4] : false;
    return $transformed_data;
  }
}
