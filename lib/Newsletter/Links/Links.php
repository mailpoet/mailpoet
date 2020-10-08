<?php

namespace MailPoet\Newsletter\Links;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Router\Endpoints\Track as TrackEndpoint;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Util\Helpers;
use MailPoet\Util\pQuery\pQuery as DomParser;
use MailPoet\Util\Security;

class Links {
  const DATA_TAG_CLICK = '[mailpoet_click_data]';
  const DATA_TAG_OPEN = '[mailpoet_open_data]';
  const LINK_TYPE_SHORTCODE = 'shortcode';
  const LINK_TYPE_URL = 'link';

  public static function process($content, $newsletterId, $queueId) {
    $extractedLinks = self::extract($content);
    $savedLinks = self::load($newsletterId, $queueId);
    $processedLinks = self::hash($extractedLinks, $savedLinks);
    return self::replace($content, $processedLinks);
  }

  public static function extract($content) {
    $extractedLinks = [];
    // extract link shortcodes
    $shortcodes = new Shortcodes();
    $shortcodes = $shortcodes->extract(
      $content,
      $categories = [Link::CATEGORY_NAME]
    );
    if ($shortcodes) {
      $extractedLinks = array_map(function($shortcode) {
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
      $extractedLinks[] = [
        'type' => self::LINK_TYPE_URL,
        'link' => $link->href,
      ];
    }
    return array_unique($extractedLinks, SORT_REGULAR);
  }

  public static function replace($content, $processedLinks) {
    // replace HTML anchor tags
    $DOM = DomParser::parseStr($content);
    foreach ($DOM->query('a') as $link) {
      $linkToReplace = $link->href;
      $replacementLink = (!empty($processedLinks[$linkToReplace]['processed_link'])) ?
        $processedLinks[$linkToReplace]['processed_link'] :
        null;
      if (!$replacementLink) continue;
      $link->setAttribute('href', $replacementLink);
    }
    $content = $DOM->__toString();
    // replace link shortcodes and markdown links
    foreach ($processedLinks as $processedLink) {
      $linkToReplace = $processedLink['link'];
      $replacementLink = $processedLink['processed_link'];
      if ($processedLink['type'] == self::LINK_TYPE_SHORTCODE) {
        $content = str_replace($linkToReplace, $replacementLink, $content);
      }
      $content = preg_replace(
        '/\[(.*?)\](\(' . preg_quote($linkToReplace, '/') . '\))/',
        '[$1](' . $replacementLink . ')',
        $content
      );
    }
    return [
      $content,
      array_values($processedLinks),
    ];
  }

  public static function replaceSubscriberData(
    $subscriberId,
    $queueId,
    $content,
    $preview = false
  ) {
    // match data tags
    $subscriber = Subscriber::findOne($subscriberId);
    preg_match_all(self::getLinkRegex(), $content, $matches);
    foreach ($matches[1] as $index => $match) {
      $hash = null;
      if (preg_match('/-/', $match)) {
        list(, $hash) = explode('-', $match);
      }
      $linkTokens = new LinkTokens;
      $data = self::createUrlDataObject(
        $subscriber->id,
        $linkTokens->getToken($subscriber),
        $queueId,
        $hash,
        $preview
      );
      $routerAction = ($matches[2][$index] === self::DATA_TAG_CLICK) ?
        TrackEndpoint::ACTION_CLICK :
        TrackEndpoint::ACTION_OPEN;
      $link = Router::buildRequest(
        TrackEndpoint::ENDPOINT,
        $routerAction,
        $data
      );
      $content = str_replace($match, $link, $content);
    }
    return $content;
  }

  public static function save(array $links, $newsletterId, $queueId) {
    foreach ($links as $link) {
      if (isset($link['id']))
        continue;
      if (empty($link['hash']) || empty($link['link'])) continue;
      $newsletterLink = NewsletterLink::create();
      $newsletterLink->newsletterId = $newsletterId;
      $newsletterLink->queueId = $queueId;
      $newsletterLink->hash = $link['hash'];
      $newsletterLink->url = $link['link'];
      $newsletterLink->save();
    }
  }

  public static function ensureInstantUnsubscribeLink(array $processedLinks) {
    if (in_array(
      NewsletterLink::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      array_column($processedLinks, 'link'))
    ) {
      return $processedLinks;
    }
    $processedLinks[] = self::hashLink(
      NewsletterLink::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      Links::LINK_TYPE_SHORTCODE
    );
    return $processedLinks;
  }

  public static function convertHashedLinksToShortcodesAndUrls($content, $queueId, $convertAll = false) {
    preg_match_all(self::getLinkRegex(), $content, $links);
    $links = array_unique(Helpers::flattenArray($links));
    foreach ($links as $link) {
      $linkHash = explode('-', $link);
      if (!isset($linkHash[1])) continue;
      $newsletterLink = NewsletterLink::where('hash', $linkHash[1])
        ->where('queue_id', $queueId)
        ->findOne();
      // convert either only link shortcodes or all hashes links if "convert all"
      // option is specified
      if (($newsletterLink instanceof NewsletterLink) &&
        (preg_match('/\[link:/', $newsletterLink->url) || $convertAll)
      ) {
        $content = str_replace($link, $newsletterLink->url, $content);
      }
    }
    return $content;
  }

  public static function getLinkRegex() {
    return sprintf(
      '/((%s|%s)(?:-\w+)?)/',
      preg_quote(self::DATA_TAG_CLICK),
      preg_quote(self::DATA_TAG_OPEN)
    );
  }

  public static function createUrlDataObject(
    $subscriberId, $subscriberLinkToken, $queueId, $linkHash, $preview
  ) {
    return [
      $subscriberId,
      $subscriberLinkToken,
      $queueId,
      $linkHash,
      $preview,
    ];
  }

  public static function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformedData = [];
    $transformedData['subscriber_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformedData['subscriber_token'] = (!empty($data[1])) ? $data[1] : false;
    $transformedData['queue_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformedData['link_hash'] = (!empty($data[3])) ? $data[3] : false;
    $transformedData['preview'] = (!empty($data[4])) ? $data[4] : false;
    return $transformedData;
  }

  private static function hashLink($link, $type) {
    $hash = Security::generateHash();
    return [
      'type' => $type,
      'hash' => $hash,
      'link' => $link,
      // replace link with a temporary data tag + hash
      // it will be further replaced with the proper track API URL during sending
      'processed_link' => self::DATA_TAG_CLICK . '-' . $hash,
    ];
  }

  private static function hash($extractedLinks, $savedLinks) {
    $processedLinks = array_map(function(&$link) {
      $link['type'] = Links::LINK_TYPE_URL;
      $link['link'] = $link['url'];
      $link['processed_link'] = self::DATA_TAG_CLICK . '-' . $link['hash'];
      return $link;
    }, $savedLinks);
    foreach ($extractedLinks as $extractedLink) {
      $link = $extractedLink['link'];
      if (array_key_exists($link, $processedLinks))
        continue;
      // Use URL as a key to map between extracted and processed links
      // regardless of their sequential position (useful for link skips etc.)
      $processedLinks[$link] = self::hashLink($link, $extractedLink['type']);
    }
    return $processedLinks;
  }

  private static function load($newsletterId, $queueId) {
    $links = NewsletterLink::whereEqual('newsletter_id', $newsletterId)
      ->whereEqual('queue_id', $queueId)
      ->findMany();
    $savedLinks = [];
    foreach ($links as $link) {
      $savedLinks[$link->url] = $link->asArray();
    }
    return $savedLinks;
  }
}
