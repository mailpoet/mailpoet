<?php

namespace MailPoet\Statistics;

use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;
use MailPoet\Util\SecondLevelDomainNames;

class GATracking {

  /** @var SecondLevelDomainNames */
  private $secondLevelDomainNames;

  public function __construct() {
    $this->secondLevelDomainNames = new SecondLevelDomainNames();
  }

  public function applyGATracking($renderedNewsletter, $newsletter, $internalHost = null) {
    if ($newsletter instanceof Newsletter && $newsletter->type == Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $parentNewsletter = $newsletter->parent()->findOne();
      $field = $parentNewsletter->gaCampaign;
    } else {
      $field = $newsletter->gaCampaign;
    }
    if (!empty($field)) {
      $renderedNewsletter = $this->addGAParamsToLinks($renderedNewsletter, $field, $internalHost);
    }
    return $renderedNewsletter;
  }

  private function addGAParamsToLinks($renderedNewsletter, $gaCampaign, $internalHost = null) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($renderedNewsletter);
    $extractedLinks = NewsletterLinks::extract($content);
    $processedLinks = $this->addParams($extractedLinks, $gaCampaign, $internalHost);
    list($content, $links) = NewsletterLinks::replace($content, $processedLinks);
    // split the processed body with hashed links back to HTML and TEXT
    list($renderedNewsletter['html'], $renderedNewsletter['text'])
      = Helpers::splitObject($content);
    return $renderedNewsletter;
  }

  private function addParams($extractedLinks, $gaCampaign, $internalHost = null) {
    $processedLinks = [];
    $params = 'utm_source=mailpoet&utm_medium=email&utm_campaign=' . urlencode($gaCampaign);
    $internalHost = $internalHost ?: parse_url(home_url(), PHP_URL_HOST);
    $internalHost = $this->secondLevelDomainNames->get($internalHost);
    foreach ($extractedLinks as $extractedLink) {
      if ($extractedLink['type'] !== NewsletterLinks::LINK_TYPE_URL) {
        continue;
      } elseif (strpos((string)parse_url($extractedLink['link'], PHP_URL_HOST), $internalHost) === false) {
        // Process only internal links (i.e. pointing to current site)
        continue;
      }
      list($path, $search, $hash) = $this->splitLink($extractedLink['link']);
      $search = empty($search) ? $params : $search . '&' . $params;
      $processedLink = $path . '?' . $search . ($hash ? '#' . $hash : '');
      $link = $extractedLink['link'];
      $processedLinks[$link] = [
        'type' => $extractedLink['type'],
        'link' => $link,
        'processed_link' => $processedLink,
      ];
    }
    return $processedLinks;
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
