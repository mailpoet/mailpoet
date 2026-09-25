<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Router\Router;
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
    return $this->addGAParamsToLinks($renderedNewsletter, $this->getGaCampaign($newsletter), $internalHost);
  }

  /**
   * Adds the params applyGATracking() bakes into links at send time to a URL that only exists
   * per recipient, e.g. a personalization tag link resolved at click time.
   *
   * URLs of MailPoet's own pages (unsubscribe, manage subscription, view in browser) are left
   * alone, so that clicks on them are not reported to GA as campaign traffic.
   */
  public function addParamsToUrl(string $url, NewsletterEntity $newsletter): string {
    if (!$this->tackingConfig->isEmailTrackingEnabled() || $this->isMailPoetUrl($url)) {
      return $url;
    }
    return $this->addParamsToInternalUrl($url, $this->getGaCampaign($newsletter), $this->getInternalDomain()) ?? $url;
  }

  private function getGaCampaign(NewsletterEntity $newsletter) {
    $parentNewsletter = $newsletter->getParent();
    if ($newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY && $parentNewsletter instanceof NewsletterEntity) {
      return $parentNewsletter->getGaCampaign();
    }
    return $newsletter->getGaCampaign();
  }

  private function isMailPoetUrl(string $url): bool {
    return strpos($url, Router::NAME) !== false || strpos($url, 'mailpoet_page=') !== false;
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
    $internalDomain = $this->getInternalDomain($internalHost);
    foreach ($extractedLinks as $extractedLink) {
      if ($extractedLink['type'] !== NewsletterLinks::LINK_TYPE_URL) {
        continue;
      }
      $link = $extractedLink['link'];
      $processedLink = $this->addParamsToInternalUrl($link, $gaCampaign, $internalDomain);
      if ($processedLink === null) {
        continue;
      }
      $processedLinks[$link] = [
        'type' => $extractedLink['type'],
        'link' => $link,
        'processed_link' => $processedLink,
      ];
    }
    return $processedLinks;
  }

  private function getInternalDomain($internalHost = null) {
    $internalHost = $internalHost ?: parse_url($this->wp->homeUrl(), PHP_URL_HOST);
    return $this->secondLevelDomainNames->get($internalHost);
  }

  /**
   * @return string|null null when the URL does not point to the current site or a
   *   mailpoet_ga_tracking_link callback returned a non-string to keep it undecorated
   */
  private function addParamsToInternalUrl(string $link, $gaCampaign, $internalDomain): ?string {
    if (strpos((string)parse_url($link, PHP_URL_HOST), $internalDomain) === false) {
      return null;
    }

    $params = [
      'utm_source' => 'mailpoet',
      'utm_medium' => 'email',
      'utm_source_platform' => 'mailpoet',
    ];
    if ($gaCampaign) {
      $params['utm_campaign'] = $gaCampaign;
    }

    // Do not overwrite existing query parameters
    $parsedUrl = parse_url($link);
    if (isset($parsedUrl['query'])) {
      foreach (array_keys($params) as $param) {
        if (strpos($parsedUrl['query'], $param . '=') !== false) {
          unset($params[$param]);
        }
      }
    }

    // Extract shortcodes from query parameters to preserve them
    list($linkWithPlaceholders, $shortcodeMap) = $this->extractShortcodes($link);

    // Add GA parameters to the link with placeholders
    $linkWithGAParams = $this->wp->addQueryArg($params, $linkWithPlaceholders);

    // Restore the original shortcodes
    $linkWithGAParams = $this->restoreShortcodes($linkWithGAParams, $shortcodeMap);

    $processedLink = $this->wp->applyFilters(
      'mailpoet_ga_tracking_link',
      $linkWithGAParams,
      $link,
      $params,
      NewsletterLinks::LINK_TYPE_URL
    );
    return is_string($processedLink) ? $processedLink : null;
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
