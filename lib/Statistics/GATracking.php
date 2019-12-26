<?php

namespace MailPoet\Statistics;

use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;
use MailPoet\Util\SecondLevelDomainNames;
use MailPoet\WP\Functions as WPFunctions;

class GATracking {

  /** @var SecondLevelDomainNames */
  private $secondLevelDomainNames;

  public function __construct() {
    $this->secondLevelDomainNames = new SecondLevelDomainNames();
  }

  public function applyGATracking($rendered_newsletter, $newsletter, $internal_host = null) {
    if ($newsletter instanceof Newsletter && $newsletter->type == Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $parent_newsletter = $newsletter->parent()->findOne();
      $field = $parent_newsletter->ga_campaign;
    } else {
      $field = $newsletter->ga_campaign;
    }
    if (!empty($field)) {
      $rendered_newsletter = $this->addGAParamsToLinks($rendered_newsletter, $field, $internal_host);
    }
    return $rendered_newsletter;
  }

  private function addGAParamsToLinks($rendered_newsletter, $ga_campaign, $internal_host = null) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($rendered_newsletter);
    $extracted_links = NewsletterLinks::extract($content);
    $processed_links = $this->addParams($extracted_links, $ga_campaign, $internal_host);
    list($content, $links) = NewsletterLinks::replace($content, $processed_links);
    // split the processed body with hashed links back to HTML and TEXT
    list($rendered_newsletter['html'], $rendered_newsletter['text'])
      = Helpers::splitObject($content);
    return $rendered_newsletter;
  }

  private function addParams($extracted_links, $ga_campaign, $internal_host = null) {
    $processed_links = [];
    $params = 'utm_source=mailpoet&utm_medium=email&utm_campaign=' . urlencode($ga_campaign);
    $internal_host = $internal_host ?: parse_url(home_url(), PHP_URL_HOST);
    $internal_host = $this->secondLevelDomainNames->get($internal_host);
    foreach ($extracted_links as $extracted_link) {
      if ($extracted_link['type'] !== NewsletterLinks::LINK_TYPE_URL) {
        continue;
      } elseif (strpos((string)parse_url($extracted_link['link'], PHP_URL_HOST), $internal_host) === false) {
        // Process only internal links (i.e. pointing to current site)
        continue;
      }
      list($path, $search, $hash) = $this->splitLink($extracted_link['link']);
      $search = empty($search) ? $params : $search . '&' . $params;
      $processed_link = $path . '?' . $search . ($hash ? '#' . $hash : '');
      $link = $extracted_link['link'];
      $processed_links[$link] = [
        'type' => $extracted_link['type'],
        'link' => $link,
        'processed_link' => $processed_link,
      ];
    }
    return $processed_links;
  }

  private function splitLink($link) {
    $parts = explode('#', $link);
    $hash = implode('#', array_slice($parts, 1));
    $parts = explode('?', $parts[0]);
    $path = $parts[0];
    $search = implode('?', array_slice($parts, 1));
    return [$path, $search, $hash];
  }
}
