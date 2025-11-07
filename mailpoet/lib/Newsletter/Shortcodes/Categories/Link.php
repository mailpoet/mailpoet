<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class Link implements CategoryInterface {
  const CATEGORY_NAME = 'link';

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var WPFunctions */
  private $wp;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    SettingsController $settings,
    NewsletterUrl $newsletterUrl,
    WPFunctions $wp,
    TrackingConfig $trackingConfig
  ) {
    $this->settings = $settings;
    $this->newsletterUrl = $newsletterUrl;
    $this->wp = $wp;
    $this->trackingConfig = $trackingConfig;
  }

  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();

    switch ($shortcodeDetails['action']) {
      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getConfirmUnsubscribeUrl(
            $wpUserPreview ? null : $subscriber,
            $queue ? $queue->getId() : null
          ),
          $queue,
          $wpUserPreview
        );

      case 'subscription_instant_unsubscribe_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getUnsubscribeUrl(
            $wpUserPreview ? null : $subscriber,
            $queue ? $queue->getId() : null
          ),
          $queue,
          $wpUserPreview
        );

      case 'subscription_manage_url':
        return self::processUrl(
          $shortcodeDetails['action'],
          $subscriptionUrlFactory->getManageUrl($wpUserPreview ? null : $subscriber),
          $queue,
          $wpUserPreview
        );

      case 'newsletter_view_in_browser_url':
        $url = $this->newsletterUrl->getViewInBrowserUrl(
          $newsletter,
          $wpUserPreview ? null : $subscriber,
          $queue,
          $wpUserPreview
        );
        return self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview);

      case 'subscription_re_engage_url':
        $url = $subscriptionUrlFactory->getReEngagementUrl($wpUserPreview ? null : $subscriber);
        return self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview);

      default:
        $shortcode = self::getFullShortcode($shortcodeDetails['action']);
        $url = $this->wp->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $shortcodeDetails['arguments'],
          $wpUserPreview
        );

        return ($url !== $shortcode) ?
          self::processUrl($shortcodeDetails['action'], $url, $queue, $wpUserPreview) :
          null;
    }
  }

  public function processUrl($action, $url, ?SendingQueueEntity $queue, $wpUserPreview = false): string {
    if ($wpUserPreview) return $url;
    return ($queue && $this->trackingConfig->isEmailTrackingEnabled()) ?
      self::getFullShortcode($action) :
      $url;
  }

  public function processShortcodeAction(
    $shortcodeAction,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    $wpUserPreview = false
  ): ?string {
    // Parse shortcode to extract action and arguments
    $shortcodeDetails = $this->parseShortcode($shortcodeAction);
    $action = $shortcodeDetails['action'];
    $arguments = $shortcodeDetails['arguments'];

    $subscriptionUrlFactory = SubscriptionUrlFactory::getInstance();
    switch ($action) {
      case 'subscription_unsubscribe_url':
        $url = $subscriptionUrlFactory->getConfirmUnsubscribeUrl(
          $subscriber,
          $queue ? $queue->getId() : null
        );
        break;
      case 'subscription_instant_unsubscribe_url':
        $url = $subscriptionUrlFactory->getUnsubscribeUrl(
          $subscriber,
          $queue ? $queue->getId() : null
        );
        break;
      case 'subscription_manage_url':
        $url = $subscriptionUrlFactory->getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = $this->newsletterUrl->getViewInBrowserUrl(
          $newsletter,
          $subscriber,
          $queue,
          false
        );
        break;
      case 'subscription_re_engage_url':
        $url = $subscriptionUrlFactory->getReEngagementUrl($subscriber);
        break;
      default:
        $shortcode = self::getFullShortcode($action);
        $url = $this->wp->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $arguments,
          $wpUserPreview
        );
        $url = ($url !== $shortcode) ? $url : null;
        break;
    }
    return $url;
  }

  private function getFullShortcode($action): string {
    return sprintf('[link:%s]', $action);
  }

  /**
   * Parse a shortcode string to extract action and arguments.
   * Supports both MailPoet-style [link:action | arg:value] and WordPress-style [link:action arg="value"].
   */
  private function parseShortcode(string $shortcode): array {
    // Decode HTML entities in case the shortcode came from HTML content (e.g., &quot; -> ")
    $shortcode = html_entity_decode($shortcode, ENT_QUOTES, 'UTF-8');

    $action = null;

    // Try WordPress-style shortcode parsing first (supports multiple arguments)
    $atts = $this->wp->shortcodeParseAtts(trim($shortcode, '[]'));
    if (!empty($atts[0]) && strpos($atts[0], ':') !== false) {
      [, $action] = explode(':', $atts[0], 2);
      $arguments = [];
      foreach ($atts as $attrName => $attrValue) {
        if (!is_numeric($attrName)) {
          // Strip surrounding quotes from attribute values
          $arguments[$attrName] = trim($attrValue, '"\' ');
        }
      }
      // Only return if we found at least one named attribute
      // Otherwise, fall through to try MailPoet-style parsing
      if (!empty($arguments)) {
        return ['action' => $action, 'arguments' => $arguments];
      }
    }

    // Fallback to MailPoet-style parsing (single argument with pipe syntax)
    // Pattern matches: [link:action | argument:value] or [link:action]
    // Example: [link:custom_link | token:abc123]
    // Captures:
    //   - action: \w+ (word characters only, e.g., "custom_link")
    //   - argument: \w+ (word characters only, e.g., "token")
    //   - argument_value: .*? (any characters, non-greedy, e.g., "abc123")
    // Note: Only supports a single argument with pipe syntax
    if (preg_match('/\[link:(?P<action>\w+)(?:.*?\|.*?(?P<argument>\w+):(?P<argument_value>.*?))?\]/', $shortcode, $match)) {
      $arguments = [];
      if (!empty($match['argument'])) {
        $arguments[$match['argument']] = $match['argument_value'] ?? '';
      }
      return ['action' => $match['action'], 'arguments' => $arguments];
    }

    // Simple action-only shortcode without arguments
    // Pattern matches: [link:action]
    // Example: [link:unsubscribe]
    // Captures:
    //   - action: \w+ (word characters only, e.g., "unsubscribe")
    if (preg_match('/\[link:(?P<action>\w+)\]/', $shortcode, $match)) {
      return ['action' => $match['action'], 'arguments' => []];
    }

    // If all parsing fails, return the action from WordPress parser if available
    // Otherwise return the shortcode as action with no arguments
    if ($action !== null) {
      return ['action' => $action, 'arguments' => []];
    }

    return ['action' => $shortcode, 'arguments' => []];
  }
}
