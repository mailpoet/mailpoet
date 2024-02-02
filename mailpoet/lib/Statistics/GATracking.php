<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Util\Helpers;
use MailPoet\Util\SecondLevelDomainNames;
use MailPoet\WP\Functions;

class GATracking {

  /** @var SecondLevelDomainNames */
  private $secondLevelDomainNames;

  /** @var NewsletterLinks */
  private $newsletterLinks;

  /** @var Functions */
  private $wp;

  public function __construct(
    NewsletterLinks $newsletterLinks,
    Functions $wp
  ) {
    $this->secondLevelDomainNames = new SecondLevelDomainNames();
    $this->newsletterLinks = $newsletterLinks;
    $this->wp = $wp;
  }

  public function applyGATracking($renderedNewsletter, NewsletterEntity $newsletter, $internalHost = null) {
    if ($newsletter->getType() == NewsletterEntity::TYPE_NOTIFICATION_HISTORY && $newsletter->getParent() instanceof NewsletterEntity) {
      $parentNewsletter = $newsletter->getParent();
      $field = $parentNewsletter->getGaCampaign();
    } else {
      $field = $newsletter->getGaCampaign();
    }

    return $this->addGAParamsToLinks($renderedNewsletter, $field, $internalHost);
  }

  private function addGAParamsToLinks($renderedNewsletter, $gaCampaign, $internalHost = null) {
    // join HTML and TEXT rendered body into a text string
    $content = Helpers::joinObject($renderedNewsletter);
    $extractedLinks = $this->newsletterLinks->extract($content);
    $processedLinks = $this->addParams($extractedLinks, $gaCampaign, $internalHost);
    list($content, $links) = $this->newsletterLinks->replace($content, $processedLinks);
    // split the processed body with hashed links back to HTML and TEXT
    list($renderedNewsletter['html'], $renderedNewsletter['text'])
      = Helpers::splitObject($content);
    return $renderedNewsletter;
  }

  private function addParams($extractedLinks, $gaCampaign, $internalHost = null) {
    $processedLinks = [];
    $params = [
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
    ];
    if ($gaCampaign) {
      $params['utm_campaign'] = $gaCampaign;
    }
    $internalHost = $internalHost ?: parse_url(home_url(), PHP_URL_HOST);
    $internalHost = $this->secondLevelDomainNames->get($internalHost);
    foreach ($extractedLinks as $extractedLink) {
      if ($extractedLink['type'] !== NewsletterLinks::LINK_TYPE_URL) {
        continue;
      } elseif (strpos((string)parse_url($extractedLink['link'], PHP_URL_HOST), $internalHost) === false) {
        // Process only internal links (i.e. pointing to current site)
        continue;
      }

      $processedLink = $this->wp->applyFilters(
        'mailpoet_ga_tracking_link',
        $this->wp->addQueryArg($params, $extractedLink['link']),
        $extractedLink['link'],
        $params,
        $extractedLink['type']
      );
      $link = $extractedLink['link'];
      $processedLinks[$link] = [
        'type' => $extractedLink['type'],
        'link' => $link,
        'processed_link' => $processedLink,
      ];
    }
    return $processedLinks;
  }
}
