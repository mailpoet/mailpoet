<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscription\Url as SubscriptionUrl;
use MailPoet\WP\Functions as WPFunctions;

class Link {
  const CATEGORY_NAME = 'link';

  static function process(
    $shortcode_details,
    $newsletter,
    $subscriber,
    $queue,
    $content,
    $wp_user_preview
  ) {
    switch ($shortcode_details['action']) {
      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $shortcode_details['action'],
          SubscriptionUrl::getUnsubscribeUrl($wp_user_preview ? null : $subscriber),
          $queue,
          $wp_user_preview
        );

      case 'subscription_manage_url':
        return self::processUrl(
          $shortcode_details['action'],
          SubscriptionUrl::getManageUrl($wp_user_preview ? null : $subscriber),
          $queue,
          $wp_user_preview
        );

      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $type = null,
          $newsletter,
          $wp_user_preview ? false : $subscriber,
          $queue,
          $wp_user_preview
        );
        return self::processUrl($shortcode_details['action'], $url, $queue, $wp_user_preview);

      default:
        $shortcode = self::getFullShortcode($shortcode_details['action']);
        $url = WPFunctions::get()->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        return ($url !== $shortcode) ?
          self::processUrl($shortcode_details['action'], $url, $queue, $wp_user_preview) :
          false;
    }
  }

  static function processUrl($action, $url, $queue, $wp_user_preview = false) {
    if ($wp_user_preview) return $url;
    $settings = new SettingsController();
    return ($queue !== false && (boolean)$settings->get('tracking.enabled')) ?
      self::getFullShortcode($action) :
      $url;
  }

  static function processShortcodeAction(
    $shortcode_action, $newsletter, $subscriber, $queue, $wp_user_preview
  ) {
    switch ($shortcode_action) {
      case 'subscription_unsubscribe_url':
        $settings = new SettingsController();
        // track unsubscribe event
        if ((boolean)$settings->get('tracking.enabled') && !$wp_user_preview) {
          $unsubscribe_event = new Unsubscribes();
          $unsubscribe_event->track($newsletter->id, $subscriber->id, $queue->id);
        }
        $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
        break;
      case 'subscription_manage_url':
        $url = SubscriptionUrl::getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl(
          $type = null,
          $newsletter,
          $subscriber,
          $queue
        );
        break;
      default:
        $shortcode = self::getFullShortcode($shortcode_action);
        $url = WPFunctions::get()->applyFilters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        $url = ($url !== $shortcode_action) ? $url : false;
        break;
    }
    return $url;
  }

  private static function getFullShortcode($action) {
    return sprintf('[link:%s]', $action);
  }
}
