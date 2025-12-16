<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Settings\TrackingConfig;
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

  /** @var TrackingConfig */
  private $tackingConfig;

  public function __construct(
    NewsletterLinks $newsletterLinks,
    Functions $wp,
    TrackingConfig $trackingConfig
  ) {
    $this->secondLevelDomainNames = new SecondLevelDomainNames();
    $this->newsletterLinks = $newsletterLinks;
    $this->wp = $wp;
    $this->tackingConfig = $trackingConfig;
  }

  public function applyGATracking($renderedNewsletter, NewsletterEntity $newsletter, $internalHost = null) {
    if (!$this->tackingConfig->isEmailTrackingEnabled()) {
      return $renderedNewsletter;
    }
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
      'utm_source_platform' => 'mailpoet',
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

      $link = $extractedLink['link'];

      // Do not overwrite existing query parameters
      $parsedUrl = parse_url($link);
      $linkParams = $params;
      if (isset($parsedUrl['query'])) {
        foreach (array_keys($params) as $param) {
          if (strpos($parsedUrl['query'], $param . '=') !== false) {
            unset($linkParams[$param]);
          }
        }
      }

      // Extract shortcodes from query parameters to preserve them
      list($linkWithPlaceholders, $shortcodeMap) = $this->extractShortcodes($link);

      // Add GA parameters to the link with placeholders
      $linkWithGAParams = $this->wp->addQueryArg($linkParams, $linkWithPlaceholders);

      // Restore the original shortcodes
      $linkWithGAParams = $this->restoreShortcodes($linkWithGAParams, $shortcodeMap);

      $processedLink = $this->wp->applyFilters(
        'mailpoet_ga_tracking_link',
        $linkWithGAParams,
        $extractedLink['link'],
        $linkParams,
        $extractedLink['type']
      );
      $processedLinks[$link] = [
        'type' => $extractedLink['type'],
        'link' => $link,
        'processed_link' => $processedLink,
      ];
    }
    return $processedLinks;
  }

  /**
   * Extract shortcodes from URL query parameter values and replace them with placeholders.
   * Shortcodes use the format [shortcode:value|option:value] and should not be URL-encoded.
   * Only shortcodes that are actual parameter values (or part of them) are extracted.
   *
   * @return array [$urlWithPlaceholders, $shortcodeMap]
   */
  private function extractShortcodes(string $url): array {
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['query'])) {
      return [$url, []];
    }

    // Parse the query string into parameters to validate shortcodes are in parameter values
    parse_str($parsedUrl['query'], $params);

    $shortcodeMap = [];
    $urlWithPlaceholders = $url;
    $index = 0;

    // Process each parameter value (recursively for arrays)
    $this->processParamsForShortcodes($params, $urlWithPlaceholders, $shortcodeMap, $index);

    return [$urlWithPlaceholders, $shortcodeMap];
  }

  /**
   * Process parameter values recursively to find and replace shortcodes.
   * Handles both string values and nested arrays.
   *
   * @param array $params Parameter values to process
   * @param string $urlWithPlaceholders URL being modified (passed by reference)
   * @param array $shortcodeMap Map of placeholders to shortcodes (passed by reference)
   * @param int $index Current placeholder index (passed by reference)
   */
  private function processParamsForShortcodes(array $params, string &$urlWithPlaceholders, array &$shortcodeMap, int &$index): void {
    foreach ($params as $value) {
      if (is_array($value)) {
        // Recursively process array values
        $this->processParamsForShortcodes($value, $urlWithPlaceholders, $shortcodeMap, $index);
      } elseif (is_string($value)) {
        // Find shortcodes in string values
        // Pattern matches MailPoet shortcodes in the format [name:value|option:value]
        // - \[ matches opening bracket
        // - [^\]]{1,400} matches 1-400 characters that are not a closing bracket
        //   (limit prevents ReDoS attacks from catastrophic backtracking)
        // - \] matches closing bracket
        // Examples: [subscriber:email], [subscriber:firstname|default:Guest]
        $pattern = '/\[[^\]]{1,400}\]/';
        if (preg_match_all($pattern, $value, $matches)) {
          foreach ($matches[0] as $shortcode) {
            // Create a unique placeholder
            $placeholder = 'MPSHORTCODE' . $index . 'MPEND';
            $shortcodeMap[$placeholder] = $shortcode;
            // Replace shortcode with placeholder directly in the URL
            $urlWithPlaceholders = str_replace($shortcode, $placeholder, $urlWithPlaceholders);
            $index++;
          }
        }
      }
    }
  }

  /**
   * Restore shortcodes in the URL by replacing placeholders with original shortcodes.
   */
  private function restoreShortcodes(string $url, array $shortcodeMap): string {
    if (empty($shortcodeMap)) {
      return $url;
    }

    foreach ($shortcodeMap as $placeholder => $shortcode) {
      $url = str_replace($placeholder, $shortcode, $url);
    }

    return $url;
  }
}
