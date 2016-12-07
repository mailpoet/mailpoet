<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Setting;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscription\Url as SubscriptionUrl;

class Link {
  static function process(
    $action,
    $default_value,
    $newsletter,
    $subscriber,
    $queue,
    $content,
    $wp_user_preview
  ) {
    switch($action) {
      case 'subscription_unsubscribe':
        $action = 'subscription_unsubscribe_url';
        $url = self::processUrl(
          $action,
          esc_attr(SubscriptionUrl::getUnsubscribeUrl($subscriber)),
          $queue,
          $wp_user_preview
        );
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('Unsubscribe', 'mailpoet')
        );

      case 'subscription_unsubscribe_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getUnsubscribeUrl($subscriber),
          $queue,
          $wp_user_preview
        );

      case 'subscription_manage':
        $url = self::processUrl(
          $action = 'subscription_manage_url',
          esc_attr(SubscriptionUrl::getManageUrl($subscriber)),
          $queue,
          $wp_user_preview
        );
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('Manage subscription', 'mailpoet')
        );

      case 'subscription_manage_url':
        return self::processUrl(
          $action,
          SubscriptionUrl::getManageUrl($subscriber),
          $queue,
          $wp_user_preview
        );

      case 'newsletter_view_in_browser':
        $action = 'newsletter_view_in_browser_url';
        $url = esc_attr(NewsletterUrl::getViewInBrowserUrl($newsletter, $subscriber, $queue));
        $url = self::processUrl($action, $url, $queue, $wp_user_preview);
        return sprintf(
          '<a target="_blank" href="%s">%s</a>',
          $url,
          __('View in your browser', 'mailpoet')
        );

      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl($newsletter, $subscriber, $queue);
        return self::processUrl($action, $url, $queue, $wp_user_preview);

      default:
        $shortcode = self::getShortcode($action);
        $url = apply_filters(
          'mailpoet_newsletter_shortcode_link',
          $shortcode,
          $newsletter,
          $subscriber,
          $queue,
          $wp_user_preview
        );
        return ($url !== $shortcode) ?
          self::processUrl($action, $url, $queue, $wp_user_preview) :
          false;
    }
  }

  static function processUrl($action, $url, $queue, $wp_user_preview = false) {
    if($wp_user_preview) return '#';
    return ($queue !== false && (boolean)Setting::getValue('tracking.enabled')) ?
      self::getShortcode($action) :
      $url;
  }

  static function processShortcodeAction(
    $shortcode_action, $newsletter, $subscriber, $queue, $wp_user_preview
  ) {
    switch($shortcode_action) {
      case 'subscription_unsubscribe_url':
        // track unsubscribe event
        if((boolean)Setting::getValue('tracking.enabled') && !$wp_user_preview) {
          $unsubscribe_event = new Unsubscribes();
          $unsubscribe_event->track($newsletter->id, $subscriber->id, $queue->id);
        }
        $url = SubscriptionUrl::getUnsubscribeUrl($subscriber);
        break;
      case 'subscription_manage_url':
        $url = SubscriptionUrl::getManageUrl($subscriber);
        break;
      case 'newsletter_view_in_browser_url':
        $url = NewsletterUrl::getViewInBrowserUrl($newsletter, $subscriber, $queue);
        break;
      default:
        $shortcode = self::getShortcode($shortcode_action);
        $url = apply_filters(
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

  private static function getShortcode($action) {
    return sprintf('[link:%s]', $action);
  }
}